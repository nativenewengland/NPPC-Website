#!/usr/bin/env bash
# Batch 310: Four verified animal-rights activists; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-310.sh --dry-run
# sudo -u www-data bash database/data/run-batch-310.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-310.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch310.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 310 || ($payload["expected_count"] ?? null) !== 4 || count($payload["entries"] ?? []) !== 4) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 310."); }
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
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id", "imprisoned_for_months"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string", "imprisoned_for_months" => "sometimes|integer|min:1"])->validate();
};
foreach ($payload["institutions"] ?? [] as $id => $name) { if (Institution::whereKey($id)->value("name") !== $name) { throw new \RuntimeException("Institution identity mismatch."); } }
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:2000s,2010s,2020s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka", "website", "gender", "race", "inmate_number"])) { throw new \RuntimeException("Unexpected profile field."); }
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
    $expected = ["alexandra-paul" => ["name" => "Alexandra Paul", "first_name" => "Alexandra", "middle_name" => "Elizabeth", "last_name" => "Paul", "aka" => "Alexandra Elizabeth Paul", "description" => "Alexandra Paul is an actor and activist whose work includes opposition to the Iraq War and animal rescue. She served jail time for antiwar civil disobedience in 2003 and for animal-rights actions in 2019 and 2026. Her entries distinguish these separate custody episodes.", "affiliation" => ["Direct Action Everywhere", "Antiwar movement"], "state" => "California", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => "https://alexandrapaul.com/", "gender" => null, "race" => null, "inmate_number" => null], "amber-canavan" => ["name" => "Amber Canavan", "first_name" => "Amber", "middle_name" => null, "last_name" => "Canavan", "aka" => null, "description" => "Amber Canavan is an animal-rights activist and Animal Protection and Rescue League volunteer. She investigated conditions at Hudson Valley Foie Gras and participated in rescuing ducks. A criminal-trespass conviction resulted in county-jail custody in 2015.", "affiliation" => ["Animal Protection and Rescue League"], "state" => "New York", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "melany-p-brieno" => ["name" => "Melany P. Brieno", "first_name" => "Melany", "middle_name" => "P.", "last_name" => "Brieno", "aka" => "Melany Brieno", "description" => "Melany P. Brieno participated in the April 2026 animal-rights action at Ridglan Farms in Wisconsin. She was detained for several days following the attempted rescue of beagles and then released on a signature bond.", "affiliation" => ["Animal rights movement"], "state" => "Wisconsin", "era" => "2020s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "adam-durand" => ["name" => "Adam Durand", "first_name" => "Adam", "middle_name" => null, "last_name" => "Durand", "aka" => null, "description" => "Adam Durand is an animal-rights advocate and videographer who documented conditions at Wegmans Egg Farm and participated in rescuing hens. He was convicted of trespass and jailed in 2006. In a later interview he described his confinement and release during the appeal process.", "affiliation" => ["Animal rights movement"], "state" => "New York", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["alexandra-paul" => ["birthdate" => ["year" => 1963, "month" => 7, "day" => 29]], "amber-canavan" => [], "melany-p-brieno" => [], "adam-durand" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["alexandra-paul" => [["arrest_date" => ["year" => 2003, "month" => 3, "day" => 19], "sentenced_date" => ["year" => 2003, "month" => 6, "day" => 11], "incarceration_date" => ["year" => 2003, "month" => 6], "release_date" => ["year" => 2003, "month" => 6]], ["arrest_date" => ["year" => 2019], "incarceration_date" => ["year" => 2019], "release_date" => ["year" => 2019]], ["arrest_date" => ["year" => 2026, "month" => 3, "day" => 15], "incarceration_date" => ["year" => 2026, "month" => 3, "day" => 15], "release_date" => ["year" => 2026, "month" => 3, "day" => 17]]], "amber-canavan" => [["incarceration_date" => ["year" => 2015, "month" => 6, "day" => 30], "release_date" => ["year" => 2015]]], "melany-p-brieno" => [["arrest_date" => ["year" => 2026, "month" => 4, "day" => 18], "incarceration_date" => ["year" => 2026, "month" => 4, "day" => 18], "release_date" => ["year" => 2026, "month" => 4, "day" => 21]]], "adam-durand" => [["sentenced_date" => ["year" => 2006, "month" => 5, "day" => 16], "incarceration_date" => ["year" => 2006, "month" => 5], "release_date" => ["year" => 2006]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["alexandra-paul" => ["Alexandra Paul", "Alexandra Elizabeth Paul"], "amber-canavan" => ["Amber Canavan"], "melany-p-brieno" => ["Melany P. Brieno", "Melany Brieno"], "adam-durand" => ["Adam Durand"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["alexandra-paul" => [["charges" => "Failing to comply with an officer — two counts arising from antiwar civil disobedience", "sentence" => "Pleaded guilty following March 19 and April 30, 2003 actions at the Los Angeles federal building. Sentenced June 11 to six days, three per count, after declining a fine or community service. Her official biography reports five days actually served at the Metropolitan Detention Center. A June 19 letter confirms she was already released; it does not establish the exact release day."], ["charges" => "Arrest during duck-farm protest — individual filed charge unverified", "sentence" => "Her official biography records two days in Sonoma County Jail after a 2019 duck-farm protest. Exact commitment/release days and the individual charging disposition remain unverified."], ["charges" => "Arrest during Ridglan Farms animal rescue — individual filed charge unverified", "sentence" => "Held in Dane County Jail following the March 15, 2026 beagle-rescue action; released March 17. Her account confirms two days in jail. This records pretrial detention, without asserting a conviction or sentence."]], "amber-canavan" => [["charges" => "Criminal trespass — misdemeanor following Hudson Valley Foie Gras investigation", "sentence" => "Began serving jail time June 30, 2015; the contemporary prisoner-support listing places her at Delaware County Jail. PETA and later reporting describe 30days, while a contemporaneous Spectrum report described a 45-day sentence. The differing sentence accounts are not converted into a numeric duration. Release occurred in 2015; the exact day remains unverified."]], "melany-p-brieno" => [["charges" => "Tentative conspiracy to commit burglary allegation — Ridglan Farms protest", "sentence" => "Arrested April 18, 2026 and held until the April 21 hearing, when she was released on a signature bond. The sheriff described a tentative allegation; FOX 6 reported formal charges were still anticipated for Brieno. This records pretrial custody, without assigning the other defendants\u{0027} filed counts or any conviction."]], "adam-durand" => [["charges" => "Criminal trespass in the third degree — three counts, New York Penal Law 140.10(a)", "sentence" => "The May 16, 2006 judgment followed trespass convictions and acquittals on burglary and larceny counts. Sentenced to six months, he spent 35days in jail before appellate release, according to his later interview. On June 5, 2009 the appellate court vacated the sentence and remitted for resentencing because the sentencing court had considered acquitted charges. Six months is not recorded as time actually served."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["alexandra-paul" => ["source_file" => "database/data/photos/alexandra-paul-mikel-healey.jpg", "storage_path" => "prisoners/alexandra-paul-mikel-healey.jpg", "sha256" => "6033d397b80598cf4850187c84213f13e7395f00613076bb6c2b815bfb31a271", "source_ids" => ["paul-photo"], "identity_evidence" => "Commons explicitly identifies actor/activist Alexandra Paul; visually reviewed960px thumbnail of the supplied portrait.", "credit" => "Mikel Healey / Alexandra Paul, via Wikimedia Commons; CC BY-SA3.0", "license_url" => "https://creativecommons.org/licenses/by-sa/3.0/", "changes" => "Commons previously cropped the photograph; Wikimedia960px thumbnail downloaded without further editing."], "amber-canavan" => null, "melany-p-brieno" => null, "adam-durand" => null];
    if (($entry["photo"] ?? null) !== $reviewedPhotos[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed portrait."); }
    if (isset($entry["photo"])) { $checkSources($entry["photo"]["source_ids"]); if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait bytes differ from review."); } }

}
if (($payload["expected_case_count"] ?? null) !== 6 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 6) { throw new \RuntimeException("Unexpected total case count."); }
$result = DB::transaction(function () use ($payload, $normalize, $dryRun) {
    $records = Prisoner::withoutGlobalScopes()->get(["id", "name", "aka", "first_name", "middle_name", "last_name", "slug", "sort_order"]);
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
echo "B310-OK\n";
'
run "add-affiliation-prisoners" "B310-OK" "$ADD_CODE" || exit 1
echo "Batch 310 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
