#!/usr/bin/env bash
# Batch 312: Twelve verified RAMPS and Mountain Justice activists; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-312.sh --dry-run
# sudo -u www-data bash database/data/run-batch-312.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-312.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch312.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 312 || ($payload["expected_count"] ?? null) !== 12 || count($payload["entries"] ?? []) !== 12) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 312."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:2010s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["sophia-morgan" => ["name" => "Sophia Morgan", "first_name" => "Sophia", "middle_name" => null, "last_name" => "Morgan", "aka" => null, "description" => "Sophia Morgan participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies Sophia Morgan among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "dorian-williams" => ["name" => "Dorian Williams", "first_name" => "Dorian", "middle_name" => null, "last_name" => "Williams", "aka" => null, "description" => "Dorian Williams is an environmental activist who participated in the RAMPS Hobet mine action while studying at Brandeis University. Williams reported ten days in jail after the July 2012 protest against mountaintop-removal mining.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "clark-santee" => ["name" => "Clark Santee", "first_name" => "Clark", "middle_name" => null, "last_name" => "Santee", "aka" => null, "description" => "Clark Santee participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies Clark Santee among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "george-vest" => ["name" => "George Vest", "first_name" => "George", "middle_name" => null, "last_name" => "Vest", "aka" => null, "description" => "George Vest participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies George Vest among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "van-pham" => ["name" => "Van Pham", "first_name" => "Van", "middle_name" => null, "last_name" => "Pham", "aka" => null, "description" => "Van Pham participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies Van Pham among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "matthew-k-smith" => ["name" => "Matthew K. Smith", "first_name" => "Matthew", "middle_name" => "K.", "last_name" => "Smith", "aka" => "Matthew Smith; Matt Smith", "description" => "Matthew K. Smith participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies Matthew K. Smith among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "kevin-kuenster" => ["name" => "Kevin Kuenster", "first_name" => "Kevin", "middle_name" => null, "last_name" => "Kuenster", "aka" => null, "description" => "Kevin Kuenster participated in the 2012 Hobet mine protest against mountaintop-removal coal mining in West Virginia. A contemporary RAMPS prisoner-support roster identifies Kevin Kuenster among those detained at Western Regional Jail.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "dustin-steele" => ["name" => "Dustin Steele", "first_name" => "Dustin", "middle_name" => null, "last_name" => "Steele", "aka" => null, "description" => "Dustin Steele is a West Virginia activist who joined the 2012 Hobet mine protest against mountaintop-removal coal mining. Steele spent several days at Western Regional Jail before release on bond.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "junior-walk" => ["name" => "Junior Walk", "first_name" => "Junior", "middle_name" => null, "last_name" => "Walk", "aka" => null, "description" => "Junior Walk participated in the May 2013 blockade of the Alpha Natural Resources headquarters access road near Bristol. The resulting case led to a short jail term in Bristol, Virginia, in July 2013.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)", "Mountain Justice"], "state" => "Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "jocelyn-sawyer" => ["name" => "Jocelyn Sawyer", "first_name" => "Jocelyn", "middle_name" => null, "last_name" => "Sawyer", "aka" => null, "description" => "Jocelyn Sawyer participated in the May 2013 blockade of the Alpha Natural Resources headquarters access road near Bristol. The resulting case led to a short jail term in Bristol, Virginia, in July 2013.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)", "Mountain Justice"], "state" => "Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "emily-gillespie" => ["name" => "Emily Gillespie", "first_name" => "Emily", "middle_name" => null, "last_name" => "Gillespie", "aka" => "Gabby Gillespie; Emily Gabby Gillespie", "description" => "Emily Gillespie participated in the May 2013 blockade of the Alpha Natural Resources headquarters access road near Bristol. The resulting case led to a short jail term in Bristol, Virginia, in July 2013.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)", "Mountain Justice"], "state" => "Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "nathan-walker-joseph" => ["name" => "Nathan Walker Joseph", "first_name" => "Nathan", "middle_name" => "Walker", "last_name" => "Joseph", "aka" => "Nathan Joseph; Ducky", "description" => "Nathan Walker Joseph, known as Ducky, joined a coal-barge protest at the Quincy Docks in West Virginia in May 2012. Joseph was jailed after a plea agreement and later wrote about the experience for RAMPS.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["sophia-morgan" => [], "dorian-williams" => [], "clark-santee" => [], "george-vest" => [], "van-pham" => [], "matthew-k-smith" => [], "kevin-kuenster" => [], "dustin-steele" => [], "junior-walk" => [], "jocelyn-sawyer" => [], "emily-gillespie" => [], "nathan-walker-joseph" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["sophia-morgan" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "dorian-williams" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "clark-santee" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "george-vest" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "van-pham" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "matthew-k-smith" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "kevin-kuenster" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "sentenced_date" => ["year" => 2012, "month" => 8], "release_date" => ["year" => 2012, "month" => 8]]], "dustin-steele" => [["arrest_date" => ["year" => 2012, "month" => 7, "day" => 28], "incarceration_date" => ["year" => 2012, "month" => 7, "day" => 28], "release_date" => ["year" => 2012, "month" => 8, "day" => 1]]], "junior-walk" => [["arrest_date" => ["year" => 2013, "month" => 5, "day" => 24], "sentenced_date" => ["year" => 2013, "month" => 7], "incarceration_date" => ["year" => 2013, "month" => 7], "release_date" => ["year" => 2013, "month" => 7, "day" => 30]]], "jocelyn-sawyer" => [["arrest_date" => ["year" => 2013, "month" => 5, "day" => 24], "sentenced_date" => ["year" => 2013, "month" => 7], "incarceration_date" => ["year" => 2013, "month" => 7], "release_date" => ["year" => 2013, "month" => 7, "day" => 29]]], "emily-gillespie" => [["arrest_date" => ["year" => 2013, "month" => 5, "day" => 24], "sentenced_date" => ["year" => 2013, "month" => 7], "incarceration_date" => ["year" => 2013, "month" => 7], "release_date" => ["year" => 2013, "month" => 7, "day" => 30]]], "nathan-walker-joseph" => [["arrest_date" => ["year" => 2012, "month" => 5, "day" => 24], "sentenced_date" => ["year" => 2012, "month" => 8, "day" => 30], "incarceration_date" => ["year" => 2012, "month" => 8, "day" => 30], "release_date" => ["year" => 2012, "month" => 9, "day" => 3]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["sophia-morgan" => ["Sophia Morgan"], "dorian-williams" => ["Dorian Williams"], "clark-santee" => ["Clark Santee"], "george-vest" => ["George Vest"], "van-pham" => ["Van Pham"], "matthew-k-smith" => ["Matthew K. Smith", "Matthew Smith", "Matt Smith"], "kevin-kuenster" => ["Kevin Kuenster"], "dustin-steele" => ["Dustin Steele"], "junior-walk" => ["Junior Walk"], "jocelyn-sawyer" => ["Jocelyn Sawyer"], "emily-gillespie" => ["Emily Gillespie", "Gabby Gillespie", "Emily Gabby Gillespie"], "nathan-walker-joseph" => ["Nathan Walker Joseph"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["sophia-morgan" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "dorian-williams" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration. Williams separately reported ten days actually served. The Justice also gives a conflicting July31 hearing/release date, inconsistent with its ten-day account and the contemporary RAMPS custody updates; that day is not entered."]], "clark-santee" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "george-vest" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "van-pham" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "matthew-k-smith" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "kevin-kuenster" => [["charges" => "Trespassing — Hobet mine protest; initial group reporting also describes obstruction charges", "sentence" => "Held after the July 28, 2012 protest. RAMPS reports that nineteen defendants accepted trespass pleas, a $500 fine and one year of probation at hearings on August 2 or August 7; all were out by August 7. The individual hearing and release day are not established, so August is recorded at month precision. Probation is not counted as incarceration."]], "dustin-steele" => [["charges" => "Trespass and obstruction reported in the Hobet protest arrests; final individual disposition unverified", "sentence" => "Arrested July 28, 2012 and released on bond August 1. Unlike the other nineteen defendants, Steele had not accepted the collective plea deal in the August2 report. No later conviction or completed probation term is inferred."]], "junior-walk" => [["charges" => "Blocking a public roadway — Alpha Natural Resources headquarters blockade", "sentence" => "Pleaded guilty to blocking a roadway; obstruction of justice was dropped. The agreement imposed ninety days with eighty suspended, with the ten unsuspended days reducible to five for good behavior. Actual July custody and release are documented. The initial post and update conflict on whether the plea and commitment occurred July24 or July25, so those dates retain month precision. The July30 update confirms release of the remaining prisoners following the July29 forecast. Suspended days are not recorded as time served."]], "jocelyn-sawyer" => [["charges" => "Blocking a public roadway — Alpha Natural Resources headquarters blockade", "sentence" => "Pleaded guilty to blocking a roadway; obstruction of justice was dropped. The agreement imposed ninety days with eighty suspended, with the ten unsuspended days reducible to five for good behavior. Actual July custody and release are documented. The initial post and update conflict on whether the plea and commitment occurred July24 or July25, so those dates retain month precision. The July29 update confirms release that morning, with credit for one earlier day after arrest. That initial short detention is not treated as a separate multi-day episode."]], "emily-gillespie" => [["charges" => "Blocking a public roadway — Alpha Natural Resources headquarters blockade", "sentence" => "Pleaded guilty to blocking a roadway; obstruction of justice was dropped. The agreement imposed ninety days with eighty suspended, with the ten unsuspended days reducible to five for good behavior. Actual July custody and release are documented. The initial post and update conflict on whether the plea and commitment occurred July24 or July25, so those dates retain month precision. The July30 update confirms release of the remaining prisoners following the July29 forecast. Suspended days are not recorded as time served."]], "nathan-walker-joseph" => [["charges" => "Obstruction — coal-barge protest at Quincy Docks, Chelyan", "sentence" => "Pleaded guilty and entered South Central Regional Jail August 30, 2012 on a five-day sentence. The September6 first-person account confirms release on Monday September3 after just over 96 hours in custody. The sentence and actual elapsed custody are distinguished."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch312."); }

}
if (($payload["expected_case_count"] ?? null) !== 12 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 12) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B312-OK\n";
'
run "add-affiliation-prisoners" "B312-OK" "$ADD_CODE" || exit 1
echo "Batch 312 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
