#!/usr/bin/env bash
# Batch 302: Verified book case additions and Trident II date corrections.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-302.sh --dry-run
# sudo -u www-data bash database/data/run-batch-302.sh
# Existing profiles and biographies are never modified; only reviewed case fields change.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-302.sh [--dry-run]" >&2
    exit 2
fi
nppc_psysh_dir="$(pwd)/storage/framework/psysh"
if ! (umask 077; mkdir -p "$nppc_psysh_dir/config" "$nppc_psysh_dir/data" "$nppc_psysh_dir/runtime"); then
    echo "Cannot prepare PsySH storage; run this batch as the application owner." >&2
    exit 1
fi
run() {
    local label="$1" sentinel="$2" code="$3" out status=0
    echo "--- ${label}"
    out=$(XDG_CONFIG_HOME="$nppc_psysh_dir/config" \
        XDG_DATA_HOME="$nppc_psysh_dir/data" \
        XDG_RUNTIME_DIR="$nppc_psysh_dir/runtime" \
        php artisan tinker --execute="$code" 2>&1) || status=$?
    printf '%s\n' "$out"
    if [[ $status -ne 0 ]] || ! grep -Fxq "$sentinel" <<<"$out"; then
        echo "FAILED: ${label}" >&2
        return 1
    fi
}
ADD_CODE='
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

$raw = str_replace("\r\n", "\n", File::get(base_path("database/data/fixes/batch302.json")));
if (hash("sha256", $raw) !== "7b3b1944d3fd086e078a3489f655745573a64bf09d24259ff88f78e9e5e44bf8") { throw new \RuntimeException("Unreviewed batch 302 payload; stop for source review."); }
$payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$get = function ($case, $field) {
    if (in_array($field, $case->partialDateFields(), true)) { return $case->partialDateIso($field); }
    return $case->$field === null ? null : ($field === "imprisoned_for_months" ? (int) $case->$field : $case->$field);
};
$set = function ($case, $field, $value) {
    if (in_array($field, $case->partialDateFields(), true)) {
        $parts = $value === null ? [] : array_map("intval", explode("-", $value));
        $case->setPartialDate($field, $parts[0] ?? null, $parts[1] ?? null, $parts[2] ?? null);
    } else { $case->$field = $value; }
};
$checkPerson = function ($entry) {
    $p = Prisoner::withoutGlobalScopes()->find($entry["prisoner_id"]);
    if (! $p || $p->name !== $entry["name"] || $p->in_exile || $p->currently_in_exile || $p->in_custody || ! $p->released) { throw new \RuntimeException("Profile identity or custody flags changed: ".$entry["name"]); }
    return $p;
};
$result = DB::transaction(function () use ($payload, $dryRun, $get, $set, $checkPerson) {
    $updates = [];
    $newCases = [];
    // Preflight every operation before any mutation. A later conflict rolls back the whole batch.
    foreach ($payload["updates"] as $entry) {
        $checkPerson($entry);
        $case = PrisonerCase::find($entry["case_id"]);
        if (! $case || $case->prisoner_id !== $entry["prisoner_id"] || $case->charges !== $entry["expected_charges"] || $case->death_in_custody_date || $case->in_exile_since || $case->end_of_exile) { throw new \RuntimeException("Case identity changed: ".$entry["name"]); }
        $pending = [];
        foreach ($entry["after"] as $field => $value) {
            $current = $get($case, $field);
            if ($current === $value) { continue; }
            if ($current !== $entry["before"][$field]) { throw new \RuntimeException("Conflicting current field: ".$entry["name"]." / ".$field); }
            $pending[$field] = $value;
        }
        if ($pending) { $updates[] = [$case, $pending, $entry["name"]]; }
    }
    foreach ($payload["additions"] as $entry) {
        $checkPerson($entry);
        $ids = PrisonerCase::where("prisoner_id", $entry["prisoner_id"])->pluck("id")->all();
        $matches = PrisonerCase::where("prisoner_id", $entry["prisoner_id"])->where("charges", $entry["fields"]["charges"])->where("sentence", $entry["fields"]["sentence"])->get();
        if ($matches->count() > 1) { throw new \RuntimeException("Duplicate matching episodes; review: ".$entry["name"]); }
        $existing = $matches->first();
        $withoutNew = array_values(array_diff($ids, $existing ? [$existing->id] : []));
        sort($withoutNew);
        if ($withoutNew !== $entry["expected_existing_case_ids"]) { throw new \RuntimeException("Case list changed; review possible duplicate: ".$entry["name"]); }
        foreach (PrisonerCase::whereIn("id", $withoutNew)->get() as $other) {
            $arrest = $other->partialDateIso("arrest_date");
            $target = $entry["dates"]["arrest_date"];
            $sameDate = $arrest && (str_starts_with($arrest, $target) || str_starts_with($target, $arrest));
            $text = strtolower(($other->charges ?? "")." ".($other->sentence ?? ""));
            $sameEvent = false;
            foreach ($entry["duplicate_terms"] as $term) {
                if (str_contains($text, $term) && ($arrest === null || str_starts_with($arrest, substr($target, 0, 4)))) { $sameEvent = true; }
            }
            if ($sameDate || $sameEvent) { throw new \RuntimeException("Possible existing episode; review before adding: ".$entry["name"]); }
        }
        if ($existing) {
            foreach (array_merge($entry["fields"], $entry["dates"]) as $field => $value) {
                if ($get($existing, $field) !== $value) { throw new \RuntimeException("Previously added case was edited; preserve and review: ".$entry["name"]); }
            }
            continue;
        }
        $newCases[] = $entry;
    }
    foreach ($updates as [$case, $fields, $name]) {
        echo ($dryRun ? "Would update case: " : "Updating case: "), $name, "\n";
        if ($dryRun) { continue; }
        foreach ($fields as $field => $value) { $set($case, $field, $value); }
        $case->save();
    }
    foreach ($newCases as $entry) {
        echo ($dryRun ? "Would add case: " : "Adding case: "), $entry["name"], "\n";
        if ($dryRun) { continue; }
        $case = new PrisonerCase($entry["fields"]);
        $case->prisoner_id = $entry["prisoner_id"];
        foreach ($entry["dates"] as $field => $value) { $set($case, $field, $value); }
        $case->save();
    }
    return [count($updates), count($newCases)];
});
if (! $dryRun && array_sum($result) > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo "Case updates: ", $result[0], "; additions: ", $result[1], "\n";
echo "B302-OK\n";

'
run "update-verified-book-cases" "B302-OK" "$ADD_CODE" || exit 1
echo "Batch 302 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
