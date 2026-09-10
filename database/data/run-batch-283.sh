#!/usr/bin/env bash
# Batch 283: Two missing prison organizers; three documented segregation cases.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-283.sh --dry-run
# sudo -u www-data bash database/data/run-batch-283.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-283.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Institution;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch283.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 283 || ($payload["expected_count"] ?? null) !== 2 || count($payload["entries"] ?? []) !== 2) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($v) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $v))));
$checkDates = function ($dates, $allowed) {
    foreach ($dates as $field => $parts) {
        if (! in_array($field, $allowed, true) || ! is_array($parts) || array_diff(array_keys($parts), ["year", "month", "day"])) { throw new \RuntimeException("Unsupported date field or precision."); }
        Validator::make($parts, ["year" => "required|integer|between:1800,2026", "month" => "sometimes|integer|between:1,12", "day" => "sometimes|integer|between:1,31"])->validate();
        if ((isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) { throw new \RuntimeException("Invalid partial date."); }
    }
};
$checkSources = function ($ids) use ($payload) {
    if (! is_array($ids) || count($ids) === 0) { throw new \RuntimeException("Missing source references."); }
    foreach ($ids as $id) {
        if (! isset($payload["sources"][$id]["label"], $payload["sources"][$id]["url"]) || ! filter_var($payload["sources"][$id]["url"], FILTER_VALIDATE_URL) || ! preg_match("~^https?://~", $payload["sources"][$id]["url"])) { throw new \RuntimeException("Invalid source reference."); }
    }
};
$checkCase = function ($case) {
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string"])->validate();
};
foreach ($payload["institutions"] ?? [] as $id => $name) { if (Institution::whereKey($id)->value("name") !== $name) { throw new \RuntimeException("Institution identity mismatch."); } }
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1980s,2010s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka", "website"])) { throw new \RuntimeException("Unexpected profile field."); }
    if (isset($seen[$entry["key"]])) { throw new \RuntimeException("Duplicate batch key."); }
    $seen[$entry["key"]] = true;
    $names = array_unique(array_map($normalize, $entry["match_names"]));
    if (! in_array($normalize($entry["prisoner"]["name"]), $names, true)) { throw new \RuntimeException("Missing canonical match name."); }
    foreach ($names as $name) {
        if (count(explode(" ", $name)) < 2) { throw new \RuntimeException("Unsafe one-word identity key."); }
        $tokens = explode(" ", $name);
        sort($tokens);
        $identityKey = implode(" ", $tokens);
        if (isset($seenNames[$identityKey]) && $seenNames[$identityKey] !== $entry["key"]) { throw new \RuntimeException("Overlapping batch identities."); }
        $seenNames[$identityKey] = $entry["key"];
    }
    $checkDates($entry["dates"], ["birthdate", "death_date"]);

    if (isset($entry["dates"]["birthdate"], $entry["dates"]["death_date"]) && $entry["dates"]["birthdate"]["year"] > $entry["dates"]["death_date"]["year"]) { throw new \RuntimeException("Death precedes birth."); }
    if (count($entry["case_dates"]) !== count($entry["prisoner"]["cases"]) || count($entry["case_research"] ?? []) !== count($entry["case_dates"])) { throw new \RuntimeException("Case evidence/date count mismatch."); }
    foreach ($entry["prisoner"]["cases"] as $index => $case) {
        $checkCase($case);
        $checkDates($entry["case_dates"][$index], ["arrest_date", "incarceration_date", "release_date", "sentenced_date", "death_in_custody_date"]);
        $checkSources($entry["case_research"][$index]["source_ids"] ?? []);
        if (empty($entry["case_research"][$index]["custody_evidence"])) { throw new \RuntimeException("Missing episode custody evidence."); }
        if (! empty($case["institution_id"]) && ! isset($payload["institutions"][$case["institution_id"]])) { throw new \RuntimeException("Unverified institution."); }
    }
    $checkSources($entry["source_ids"] ?? []);
    if (empty($entry["custody_evidence"])) { throw new \RuntimeException("Missing actual custody evidence."); }
    $expected = ["sitawa-nantambu-jamaa" => ["name" => "Sitawa Nantambu Jamaa", "affiliation" => ["Prisoner Human Rights Movement", "Short Corridor Collective"], "state" => "California", "era" => "1980s", "lat" => 41.75, "lng" => -124.2, "released" => true, "website" => "https://sitawanantambujamaa.wordpress.com/"], "paul-alwyn-redd-jr" => ["name" => "Paul Alwyn Redd Jr.", "affiliation" => ["Prisoner Human Rights Movement", "Short Corridor Collective"], "state" => "California", "era" => "2010s", "lat" => 41.75, "lng" => -124.2, "released" => true, "website" => "https://paulreddjr.blogspot.com/"]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected identity, affiliation, location, support site or custody status."); } }
    $reviewedDates = ["sitawa-nantambu-jamaa" => [["incarceration_date" => ["year" => 1985, "month" => 5, "day" => 15]], ["incarceration_date" => ["year" => 2018, "month" => 1, "day" => 11]]], "paul-alwyn-redd-jr" => [[]]];
    $reviewedVitals = ["sitawa-nantambu-jamaa" => ["birthdate" => ["year" => 1958, "month" => 7, "day" => 13]], "paul-alwyn-redd-jr" => ["birthdate" => ["year" => 1956, "month" => 12, "day" => 2], "death_date" => ["year" => 2022, "month" => 6, "day" => 19]]];
    $reviewedCases = ["sitawa-nantambu-jamaa" => [["charges" => "Administrative segregation under a disputed Black Guerrilla Family classification", "sentence" => "More than 30 years in solitary confinement across California prisons. Jamaa’s sworn 2013 declaration describes continued isolation based on alleged associations and political materials. His 2016 account dates the initial placement at San Quentin to May 15, 1985, and departure from long-term solitary to October 11, 2015; he arrived at Salinas Valley general population on October 13. This was administrative confinement within an existing sentence, not a new criminal conviction. The end of solitary was not release from prison."], ["charges" => "Administrative segregation after an alleged contraband discovery; political retaliation alleged", "sentence" => "Jamaa reports placement in Salinas Valley administrative segregation on January 11, 2018, and continued confinement through at least January 30. He alleged that officials fabricated contraband reports in retaliation for his role in the Ashker litigation. His March 2018 account describes two classification hearings and an extension pending a disciplinary hearing. The final disposition and segregation exit date remain unverified. These are his allegations, not an established judicial finding."]], "paul-alwyn-redd-jr" => [["charges" => "Continued administrative segregation after a disputed 2011 gang-status review", "sentence" => "Redd’s sworn May 2013 declaration describes denial of inactive status in 2011 and continued confinement at Pelican Bay SHU. He said the review relied on political writings, George Jackson and Black Panther artwork, association evidence and disputed informant claims. He reported 33 of the previous 35 years in solitary, including a break from Pelican Bay between December 2000 and March 2001. This case records the documented continuation of segregation, not a new criminal conviction or an uninterrupted 35-year term. The individual exit date for this segregation episode is unresolved.", "institution_id" => "4d17ee62-74b8-4c04-9e72-a9d58580d5a8"]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["sitawa-nantambu-jamaa" => ["source_file" => "database/data/photos/sitawa-nantambu-jamaa-support-site.jpg", "storage_path" => "prisoners/sitawa-nantambu-jamaa-support-site.jpg", "sha256" => "6e93bd8734f6b62fa1a4d08e26026029448bd868cf57ccac3d88a6b6ded73854", "source_id" => "sitawa_photo"], "paul-alwyn-redd-jr" => ["source_file" => "database/data/photos/paul-redd-columbia-crop.png", "storage_path" => "prisoners/paul-redd-columbia-crop.png", "sha256" => "261207191a0121d2879489f6f865ca92bc15169497795d6ca72784ecefc57443", "source_id" => "redd_columbia"]];
    if (($entry["photo"] ?? null) !== $approvedPhotos[$entry["key"]]) { throw new \RuntimeException("Unverified portrait."); }
    $checkSources([$entry["photo"]["source_id"]]);
    if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }

}
if (($payload["expected_case_count"] ?? null) !== 3 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 3) { throw new \RuntimeException("Unexpected total case count."); }
$result = DB::transaction(function () use ($payload, $normalize, $dryRun) {
    $records = Prisoner::withoutGlobalScopes()->get();
    $missing = [];
    $preserved = 0;
    foreach ($payload["entries"] as $entry) {
        $names = array_unique(array_map($normalize, $entry["match_names"]));
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
        if ($matches->isNotEmpty()) {
            echo "Preserved existing: ", $entry["prisoner"]["name"], "\n";
            $preserved++;
        } else { $missing[] = $entry; }
    }
    $nextOrder = (int) $records->max("sort_order") + 1;
    foreach ($missing as $entry) {
        echo ($dryRun ? "Would add: " : "Adding: "), $entry["prisoner"]["name"], "\n";
        if ($dryRun) { continue; }
        $fields = $entry["prisoner"];
        $cases = $fields["cases"];
        unset($fields["cases"]);
        if (isset($entry["photo"])) {
            $photo = $entry["photo"];
            $disk = Storage::disk("public");
            if ($disk->exists($photo["storage_path"])) {
                if (hash("sha256", $disk->get($photo["storage_path"])) !== $photo["sha256"]) { throw new \RuntimeException("Refusing to overwrite different portrait bytes."); }
            } elseif (! $disk->put($photo["storage_path"], File::get(base_path($photo["source_file"])))) { throw new \RuntimeException("Could not store portrait."); }
            $fields["photo"] = $photo["storage_path"];
        }
        $record = new Prisoner($fields);
        $record->sort_order = $nextOrder++;
        foreach ($entry["dates"] as $field => $parts) { $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        $record->save();
        foreach ($cases as $index => $caseFields) {
            $case = new PrisonerCase($caseFields);
            $case->prisoner_id = $record->id;
            foreach ($entry["case_dates"][$index] as $field => $parts) { $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
            $case->save();
        }
    }
    return [count($missing), $preserved];
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo ($dryRun ? "Would add profiles: " : "Added profiles: "), $result[0], "; existing profiles preserved: ", $result[1], "\n";
echo "B283-OK\n";
'
run "add-affiliation-prisoners" "B283-OK" "$ADD_CODE" || exit 1
echo "Batch 283 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
