#!/usr/bin/env bash
# Batch 321: Fifteen verified IWW prisoners; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-321.sh --dry-run
# sudo -u www-data bash database/data/run-batch-321.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-321.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch321.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 321 || ($payload["expected_count"] ?? null) !== 15 || count($payload["entries"] ?? []) !== 15) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 321."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1910s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["henry-biddiscomb" => ["name" => "Henry Biddiscomb", "first_name" => "Henry", "middle_name" => null, "last_name" => "Biddiscomb", "aka" => "H. Biddiscomb", "description" => "Henry Biddiscomb was an Industrial Workers of the World member detained following the November 1917 union convention raid in Omaha, Nebraska. Union prisoner records identify a federal conspiracy indictment and his eventual release on bail in December 1918.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Nebraska", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "roy-decker" => ["name" => "Roy Decker", "first_name" => "Roy", "middle_name" => null, "last_name" => "Decker", "aka" => null, "description" => "Roy Decker was an Industrial Workers of the World member detained following the November 1917 union convention raid in Omaha, Nebraska. Union prisoner records identify a federal conspiracy indictment and his eventual release on bail in December 1918.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Nebraska", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "t-venier" => ["name" => "T. Venier", "first_name" => "T.", "middle_name" => null, "last_name" => "Venier", "aka" => null, "description" => "T. Venier was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ed-shanon" => ["name" => "Ed Shanon", "first_name" => "Ed", "middle_name" => null, "last_name" => "Shanon", "aka" => null, "description" => "Ed Shanon was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "nick-verbeck" => ["name" => "Nick Verbeck", "first_name" => "Nick", "middle_name" => null, "last_name" => "Verbeck", "aka" => "Nick Verbok", "description" => "Nick Verbeck was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. Unlike the other listed detainees, he remained in federal custody and later received a six-year military sentence.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "matt-antella" => ["name" => "Matt Antella", "first_name" => "Matt", "middle_name" => null, "last_name" => "Antella", "aka" => null, "description" => "Matt Antella was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "j-antella" => ["name" => "J. Antella", "first_name" => "J.", "middle_name" => null, "last_name" => "Antella", "aka" => null, "description" => "J. Antella was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "p-davison" => ["name" => "P. Davison", "first_name" => "P.", "middle_name" => null, "last_name" => "Davison", "aka" => null, "description" => "P. Davison was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "n-mccloud" => ["name" => "N. McCloud", "first_name" => "N.", "middle_name" => null, "last_name" => "McCloud", "aka" => null, "description" => "N. McCloud was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "s-hourve" => ["name" => "S. Hourve", "first_name" => "S.", "middle_name" => null, "last_name" => "Hourve", "aka" => null, "description" => "S. Hourve was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "a-l-vecellio" => ["name" => "A. L. Vecellio", "first_name" => "A.", "middle_name" => "L.", "last_name" => "Vecellio", "aka" => null, "description" => "A. L. Vecellio was an Industrial Workers of the World member detained in Idaho during the 1917 crackdown on labor organizing. A union custody roster records his July arrest and confinement at St. Maries and Moscow. It records his release on November 29, 1917. He later supplied the published roster and documented his own 1919 detention in Pennsylvania.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "neal-guiney" => ["name" => "Neal Guiney", "first_name" => "Neal", "middle_name" => null, "last_name" => "Guiney", "aka" => "Neil Guiney", "description" => "Neal Guiney, also recorded as Neil Guiney, was an Industrial Workers of the World member imprisoned in Idaho during the 1917 labor-organizing prosecutions. A later union register also identifies prolonged confinement in Portland, Oregon after another arrest in early 1919.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Idaho", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "e-pavini" => ["name" => "E. Pavini", "first_name" => "E.", "middle_name" => null, "last_name" => "Pavini", "aka" => null, "description" => "E. Pavini was an Industrial Workers of the World member detained in Eureka, California in 1918. A union custody report describes release from an initial vagrancy case followed immediately by renewed federal detention lasting until May.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "California", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "g-bertini" => ["name" => "G. Bertini", "first_name" => "G.", "middle_name" => null, "last_name" => "Bertini", "aka" => null, "description" => "G. Bertini was an Industrial Workers of the World member jailed at Fort Bragg, California in 1918. A union custody roster records forty-one days of confinement following a state arrest.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "California", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "john-levo" => ["name" => "John Levo", "first_name" => "John", "middle_name" => null, "last_name" => "Levo", "aka" => null, "description" => "John Levo was an Industrial Workers of the World member held in deportation proceedings after arrest in Seattle in January 1918. He was confined in Washington and later transferred to Ellis Island, where the union custody roster records his release in March 1919.", "affiliation" => ["Industrial Workers of the World (IWW)"], "state" => "Washington", "era" => "1910s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["henry-biddiscomb" => [], "roy-decker" => [], "t-venier" => [], "ed-shanon" => [], "nick-verbeck" => [], "matt-antella" => [], "j-antella" => [], "p-davison" => [], "n-mccloud" => [], "s-hourve" => [], "a-l-vecellio" => [], "neal-guiney" => [], "e-pavini" => [], "g-bertini" => [], "john-levo" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["henry-biddiscomb" => [["arrest_date" => ["year" => 1917, "month" => 11, "day" => 13], "incarceration_date" => ["year" => 1917, "month" => 11, "day" => 13], "release_date" => ["year" => 1918, "month" => 12, "day" => 6]]], "roy-decker" => [["arrest_date" => ["year" => 1917, "month" => 11, "day" => 13], "incarceration_date" => ["year" => 1917, "month" => 11, "day" => 13], "release_date" => ["year" => 1918, "month" => 12, "day" => 6]]], "t-venier" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 20], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 20], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "ed-shanon" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 20], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 20], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "nick-verbeck" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "sentenced_date" => ["year" => 1918, "month" => 7]]], "matt-antella" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "j-antella" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "p-davison" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "n-mccloud" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "s-hourve" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]]], "a-l-vecellio" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 23], "release_date" => ["year" => 1917, "month" => 11, "day" => 29]], ["arrest_date" => ["year" => 1919, "month" => 6, "day" => 29], "incarceration_date" => ["year" => 1919, "month" => 6, "day" => 29], "release_date" => ["year" => 1919, "month" => 7, "day" => 3]]], "neal-guiney" => [["arrest_date" => ["year" => 1917, "month" => 7, "day" => 18], "incarceration_date" => ["year" => 1917, "month" => 7, "day" => 18]], ["arrest_date" => ["year" => 1919], "incarceration_date" => ["year" => 1919]]], "e-pavini" => [["arrest_date" => ["year" => 1918, "month" => 1, "day" => 27], "incarceration_date" => ["year" => 1918, "month" => 1, "day" => 27], "release_date" => ["year" => 1918, "month" => 5, "day" => 2]]], "g-bertini" => [["arrest_date" => ["year" => 1918, "month" => 2, "day" => 7], "incarceration_date" => ["year" => 1918, "month" => 2, "day" => 7]]], "john-levo" => [["arrest_date" => ["year" => 1918, "month" => 1, "day" => 16], "incarceration_date" => ["year" => 1918, "month" => 1, "day" => 16], "release_date" => ["year" => 1919, "month" => 3, "day" => 17]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["henry-biddiscomb" => ["Henry Biddiscomb", "H. Biddiscomb"], "roy-decker" => ["Roy Decker"], "t-venier" => ["T. Venier"], "ed-shanon" => ["Ed Shanon"], "nick-verbeck" => ["Nick Verbeck", "Nick Verbok"], "matt-antella" => ["Matt Antella"], "j-antella" => ["J. Antella"], "p-davison" => ["P. Davison"], "n-mccloud" => ["N. McCloud"], "s-hourve" => ["S. Hourve"], "a-l-vecellio" => ["A. L. Vecellio"], "neal-guiney" => ["Neal Guiney", "Neil Guiney"], "e-pavini" => ["E. Pavini"], "g-bertini" => ["G. Bertini"], "john-levo" => ["John Levo"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["henry-biddiscomb" => [["charges" => "Federal conspiracy prosecution of IWW convention participants", "sentence" => "Arrested November 13, 1917. Indicted September 21, 1918; released on $1,000 bond December 6, 1918 according to the later named custody roster. The March register reports $10,000 bail, which is distinguished from the bond on which release occurred. No conviction, prison sentence or final prosecution outcome inferred."]], "roy-decker" => [["charges" => "Federal conspiracy prosecution of IWW convention participants", "sentence" => "Arrested November 13, 1917. Indicted September 21, 1918; released on $1,000 bond December 6, 1918 according to the later named custody roster. The March register reports $10,000 bail, which is distinguished from the bond on which release occurred. No conviction, prison sentence or final prosecution outcome inferred."]], "t-venier" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "ed-shanon" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "nick-verbeck" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing; subsequent military prosecution, precise charge unresolved", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Explicitly excluded from the November 29 group release: taken into federal custody, transferred to Fort Wright and sentenced by court martial in July 1918 to six years at McNeil Island. The May correction supersedes the March register statement that he was released in 1918. Exact military charge, prison admission and eventual release remain unverified; six years is a sentence, not asserted time served."]], "matt-antella" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "j-antella" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "p-davison" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "n-mccloud" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "s-hourve" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."]], "a-l-vecellio" => [["charges" => "Criminal syndicalism allegations during Idaho IWW organizing", "sentence" => "Arrested in July 1917 on criminal-syndicalism allegations. The named roster records transfer to Moscow on August 2 and back to St. Maries on September 17. Released November 29, 1917. No individual conviction or sentence asserted from the detention record."], ["charges" => "Federal investigation following detention of IWW activist", "sentence" => "Arrested at Van Voorhis, Pennsylvania June 29, 1919 and released July 3, 1919, according to his published custody report. Precise statutory charge and final legal disposition remain unspecified."]], "neal-guiney" => [["charges" => "Idaho prosecution during IWW organizing; criminal-syndicalism allegations in the named group roster", "sentence" => "The May roster dates his St. Maries arrest July 18, 1917 and describes months of detention through transfers to Moscow and back. It includes him in the November 29, 1917 release statement; the March register instead reports release in 1918. This unresolved conflict is disclosed and no release date is entered."], ["charges" => "Oregon state prosecution of IWW activist; precise charge unverified", "sentence" => "The March 1920 union register states that Guiney was rearrested in Portland early in 1919 and was still held in Portland jail, mentioning trial and appeal proceedings without clearly distinguishing them. Record year of entry only; no precise sentence, release date or present-day custody asserted."]], "e-pavini" => [["charges" => "Federal investigation after initial vagrancy detention", "sentence" => "Initial vagrancy arrest January 26, 1918; released the next day and rearrested before leaving the courthouse for federal investigation. The qualifying renewed detention began January 27 and ended May 2, 1918. The initial one-night arrest is not counted as a separate qualifying case. Exact federal statutory charge and final disposition unspecified."]], "g-bertini" => [["charges" => "State prosecution during IWW repression; precise charge unspecified", "sentence" => "Arrested at Fort Bragg February 7, 1918 and released forty-one days later, according to the named union custody report. The reported duration is retained in prose; no exact release day calculated and no formal sentence inferred."]], "john-levo" => [["charges" => "Immigration detention in deportation proceedings involving IWW members", "sentence" => "Arrested Seattle January 16, 1918; transferred to jail in Everett and later taken to Ellis Island with the Red Special detainees. Released from Ellis Island March 17, 1919 according to the named report. Release does not establish deportation or the final immigration disposition. No criminal conviction asserted."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch321."); }

}
if (($payload["expected_case_count"] ?? null) !== 17 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 17) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B321-OK\n";
'
run "add-affiliation-prisoners" "B321-OK" "$ADD_CODE" || exit 1
echo "Batch 321 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
