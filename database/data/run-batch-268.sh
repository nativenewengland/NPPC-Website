#!/usr/bin/env bash
# Batch 268: CNVA cases. Apply after earlier pending batches, in order.
# sudo -u www-data bash database/data/run-batch-268.sh --dry-run
# sudo -u www-data bash database/data/run-batch-268.sh
# No existing biographies, photos, profile fields or duration totals are replaced.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-268.sh [--dry-run]" >&2
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
use App\Models\Institution;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch268.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 268 || ($payload["expected_new_profiles"] ?? null) !== 26 || count($payload["entries"] ?? []) !== 26 || ($payload["expected_new_existing_cases"] ?? null) !== 9 || count($payload["new_cases"] ?? []) !== 9 || ($payload["expected_case_updates"] ?? null) !== 3 || count($payload["case_updates"] ?? []) !== 3) { throw new \RuntimeException("Unexpected batch identity/counts."); }
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($v) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $v))));
$blank = fn ($v) => $v === null || (is_string($v) && trim($v) === "");
$checkSources = function ($ids) use ($payload) {
    if (! is_array($ids) || ! count($ids)) { throw new \RuntimeException("Missing sources."); }
    foreach ($ids as $id) {
        $source = $payload["sources"][$id] ?? [];
        if (empty($source["label"]) || ! filter_var($source["url"] ?? null, FILTER_VALIDATE_URL) || ! preg_match("~^https?://~", $source["url"])) { throw new \RuntimeException("Invalid source."); }
    }
};
$checkDates = function ($dates) {
    foreach ($dates as $field => $parts) {
        if (! in_array($field, ["arrest_date", "sentenced_date", "incarceration_date", "release_date"], true) || ! is_array($parts) || array_diff(array_keys($parts), ["year", "month", "day"])) { throw new \RuntimeException("Unsupported date field."); }
        Validator::make($parts, ["year" => "required|integer|between:1959,1967", "month" => "sometimes|integer|between:1,12", "day" => "sometimes|integer|between:1,31"])->validate();
        if ((isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) { throw new \RuntimeException("Invalid date."); }
    }
};
$checkCase = function ($case) use ($payload) {
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "judge", "prosecutor", "institution_id", "imprisoned_for_months"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "judge" => "sometimes|string|max:255", "prosecutor" => "sometimes|string|max:255", "institution_id" => "sometimes|string", "imprisoned_for_months" => "sometimes|integer|in:6"])->validate();
    if (isset($case["institution_id"]) && ! isset($payload["institutions"][$case["institution_id"]])) { throw new \RuntimeException("Unverified institution."); }
};
$seen = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|in:Connecticut,Georgia,Massachusetts,California", "prisoner.era" => "required|in:1960s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean|accepted", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng"])) { throw new \RuntimeException("Unexpected profile field."); }
    if (! in_array($normalize($entry["prisoner"]["name"]), array_map($normalize, $entry["match_names"]), true)) { throw new \RuntimeException("Canonical name missing."); }
    foreach ($entry["match_names"] as $name) {
        $tokens = explode(" ", $normalize($name)); sort($tokens); $key = implode(" ", $tokens);
        if (count($tokens) < 2 || (isset($seen[$key]) && $seen[$key] !== $entry["key"])) { throw new \RuntimeException("Unsafe or overlapping identity."); }
        $seen[$key] = $entry["key"];
    }
    $checkCase($entry["case"]); $checkDates($entry["case_dates"]); $checkSources($entry["source_ids"] ?? []);
    foreach ($entry["extra_cases"] ?? [] as $extra) { $checkCase($extra["case"]); $checkDates($extra["case_dates"]); $checkSources($extra["source_ids"] ?? []); }
}
foreach ($payload["new_cases"] as $entry) {
    $checkCase($entry["case"]); $checkDates($entry["case_dates"]); $checkSources($entry["source_ids"] ?? []);
    Validator::make($entry, ["case_id" => "required|uuid", "prisoner_id" => "required|uuid", "name" => "required|string", "slug" => "required|string", "match_pattern" => ["required", "regex:/^[a-z .|]+$/"]])->validate();
}
foreach ($payload["case_updates"] as $entry) {
    if (array_diff(array_keys($entry["fill"]), ["plead", "judge", "prosecutor"])) { throw new \RuntimeException("Unexpected fill field."); }
    foreach ($entry["fill"] as $value) { if (! is_string($value) || strlen($value) > 255 || ! trim($value)) { throw new \RuntimeException("Invalid fill value."); } }
    if (! is_string($entry["append_sentence"]) || ! trim($entry["append_sentence"])) { throw new \RuntimeException("Missing case addition."); }
    $checkDates($entry["fill_dates"]); $checkSources($entry["source_ids"] ?? []);
}
$result = DB::transaction(function () use ($payload, $normalize, $blank, $dryRun) {
    foreach ($payload["institutions"] as $id => $name) {
        $institution = Institution::find($id);
        if (! $institution || $institution->name !== $name || $institution->state !== "Connecticut") { throw new \RuntimeException("Institution identity changed: ".$name); }
    }
    $records = Prisoner::withoutGlobalScopes()->get();
    $missing = []; $newCases = []; $updates = []; $preserved = 0;
    foreach ($payload["entries"] as $entry) {
        $names = array_map($normalize, $entry["match_names"]);
        $matches = $records->filter(function ($record) use ($names, $normalize) {
            $haystack = " ".$normalize(implode(" ", [$record->name, $record->aka, $record->first_name, $record->middle_name, $record->last_name, $record->slug]))." ";
            foreach ($names as $name) {
                $found = true;
                foreach (explode(" ", $name) as $token) { if (! str_contains($haystack, " ".$token." ")) { $found = false; break; } }
                if ($found) { return true; }
            }
            return false;
        });
        if ($matches->count() > 1) { throw new \RuntimeException("Ambiguous identity: ".$entry["prisoner"]["name"]); }
        if ($matches->isNotEmpty()) { echo "Preserved existing profile: ", $entry["prisoner"]["name"], "\n"; $preserved++; }
        else { $missing[] = $entry; }
    }
    $getPerson = function ($entry) use ($records) {
        $person = $records->firstWhere("id", $entry["prisoner_id"]);
        if (! $person || $person->slug !== $entry["slug"] || $person->name !== $entry["name"]) { throw new \RuntimeException("Existing profile identity changed: ".$entry["name"]); }
        return $person;
    };
    foreach ($payload["new_cases"] as $entry) {
        $person = $getPerson($entry);
        $byId = PrisonerCase::find($entry["case_id"]);
        if ($byId && ($byId->prisoner_id !== $person->id || $byId->charges !== $entry["case"]["charges"])) { throw new \RuntimeException("Case ID collision."); }
        $matches = $person->cases()->get()->filter(function ($case) use ($entry) {
            return $case->id === $entry["case_id"] || preg_match("/".$entry["match_pattern"]."/i", implode(" ", [$case->charges, $case->sentence]));
        });
        if ($matches->count() > 1) { throw new \RuntimeException("Ambiguous existing case: ".$entry["key"]); }
        if ($matches->isNotEmpty()) { echo "Preserved existing case: ", $entry["key"], "\n"; }
        else { $newCases[] = $entry; }
    }
    foreach ($payload["case_updates"] as $entry) {
        $person = $getPerson($entry); $case = PrisonerCase::find($entry["case_id"]);
        if (! $case || $case->prisoner_id !== $person->id || $case->charges !== $entry["expected_charges"]) { throw new \RuntimeException("Existing case identity changed: ".$person->name); }
        foreach ($entry["fill"] as $field => $value) {
            if ($blank($case->$field)) { $case->$field = $value; }
            elseif ($case->$field !== $value) { echo "Preserved populated field: ", $person->name, " / ", $field, "\n"; }
        }
        foreach ($entry["fill_dates"] as $field => $parts) {
            if ($blank($case->$field)) { $case->setPartialDate($field, $parts["year"], $parts["month"], $parts["day"]); }
        }
        if (! str_contains((string) $case->sentence, $entry["append_sentence"])) {
            if (! str_starts_with((string) $case->sentence, $entry["expected_sentence"])) { throw new \RuntimeException("Case prose changed; review before appending: ".$person->name); }
            $case->sentence = rtrim((string) $case->sentence)."\n\n".$entry["append_sentence"];
        }
        if ($case->isDirty()) { $updates[] = [$case, $person->name]; }
    }
    // Every identity and guard is checked before the first write.
    $nextOrder = (int) $records->max("sort_order") + 1;
    $saveCase = function ($entry, $personId, $caseId = null) {
        $case = new PrisonerCase($entry["case"]); $case->prisoner_id = $personId;
        if ($caseId) { $case->id = $caseId; }
        foreach ($entry["case_dates"] as $field => $parts) { $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        if ($caseId) {
            // The base creating hook replaces supplied UUIDs. Keep the stable batch ID
            // while calculating the normal duration for these restricted case fields.
            $case->imprisoned_for_days = $case->computeImprisonedForDays();
            $case->in_exile_for_days = $case->computeInExileForDays();
            $case->saveQuietly();
        } else { $case->save(); }
    };
    foreach ($missing as $entry) {
        echo ($dryRun ? "Would add profile: " : "Adding profile: "), $entry["prisoner"]["name"], "\n";
        if ($dryRun) { continue; }
        $person = new Prisoner($entry["prisoner"]); $person->sort_order = $nextOrder++; $person->save();
        $saveCase($entry, $person->id);
        foreach ($entry["extra_cases"] ?? [] as $extra) { $saveCase($extra, $person->id); }
    }
    foreach ($newCases as $entry) {
        echo ($dryRun ? "Would add case: " : "Adding case: "), $entry["name"], "\n";
        if (! $dryRun) { $saveCase($entry, $entry["prisoner_id"], $entry["case_id"]); }
    }
    foreach ($updates as [$case, $name]) {
        echo ($dryRun ? "Would fill/append case: " : "Filling/appending case: "), $name, "\n";
        if (! $dryRun) {
            // Preserve unrelated stored counters and avoid model hooks changing other fields.
            $case->saveQuietly();
        }
    }
    return [count($missing), count($newCases), count($updates), $preserved];
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo "Profiles: ", $result[0], "; new existing-profile cases: ", $result[1], "; case updates: ", $result[2], "; preserved profiles: ", $result[3], "\n";
echo "B268-OK\n";
'
run "add-verified-cnva-cases" "B268-OK" "$ADD_CODE" || exit 1
echo "Batch 268 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
