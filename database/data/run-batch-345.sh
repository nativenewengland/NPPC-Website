#!/usr/bin/env bash
# Batch 345: Fifteen verified Canton civil-rights prisoners; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-345.sh --dry-run
# sudo -u www-data bash database/data/run-batch-345.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-345.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch345.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 345 || ($payload["expected_count"] ?? null) !== 15 || count($payload["entries"] ?? []) !== 15) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 345."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1960s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["c-o-chinn" => ["name" => "C. O. Chinn", "first_name" => "C.", "middle_name" => "O.", "last_name" => "Chinn", "aka" => "C.O. Chinn; CO Chinn; C. O. Chinn Sr.", "description" => "C. O. Chinn supported voter registration and the Canton boycott. He was jailed during the January 1964 crackdown.", "affiliation" => ["Congress of Racial Equality"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "james-collier" => ["name" => "James Collier", "first_name" => "James", "middle_name" => null, "last_name" => "Collier", "aka" => "Jim Collier", "description" => "James Collier organized voter registration with SNCC in Canton.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "theodis-hewitt" => ["name" => "Theodis Hewitt", "first_name" => "Theodis", "middle_name" => null, "last_name" => "Hewitt", "aka" => "Theotis Hewitt; Theotus Hewitt", "description" => "Theodis Hewitt worked on the Canton voter-registration campaign.", "affiliation" => ["Congress of Racial Equality"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "carole-merritt" => ["name" => "Carole Merritt", "first_name" => "Carole", "middle_name" => null, "last_name" => "Merritt", "aka" => "Carol Merritt; Carole Elaine Merritt", "description" => "Carole Merritt was a SNCC field worker in Canton.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "richard-jewett" => ["name" => "Richard Jewett", "first_name" => "Richard", "middle_name" => null, "last_name" => "Jewett", "aka" => "Dick Jewett; Richard Jewitt; Dick Jewitt", "description" => "Richard Jewett participated in Canton civil-rights organizing.", "affiliation" => ["Congress of Racial Equality"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "patricia-meyers" => ["name" => "Patricia Meyers", "first_name" => "Patricia", "middle_name" => null, "last_name" => "Meyers", "aka" => "Patricia Myers", "description" => "Patricia Meyers participated in Canton civil-rights organizing.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "martha-jones" => ["name" => "Martha Jones", "first_name" => "Martha", "middle_name" => null, "last_name" => "Jones", "aka" => "Martha James Jones", "description" => "Martha Jones participated in Canton civil-rights organizing.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "william-veal" => ["name" => "William Veal", "first_name" => "William", "middle_name" => null, "last_name" => "Veal", "aka" => null, "description" => "William Veal participated in Canton civil-rights organizing.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "sylvester-lee-palmer" => ["name" => "Sylvester Lee Palmer", "first_name" => "Sylvester", "middle_name" => "Lee", "last_name" => "Palmer", "aka" => "Sylvester Palmer", "description" => "Sylvester Lee Palmer participated in Canton civil-rights organizing.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "alma-bosley" => ["name" => "Alma Bosley", "first_name" => "Alma", "middle_name" => null, "last_name" => "Bosley", "aka" => null, "description" => "Alma Bosley was a CORE worker in Canton.", "affiliation" => ["Congress of Racial Equality"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "pete-hewitt" => ["name" => "Pete Hewitt", "first_name" => "Pete", "middle_name" => null, "last_name" => "Hewitt", "aka" => "Peter Hewitt", "description" => "Pete Hewitt participated in the Canton voter-registration campaign.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "milton-esco" => ["name" => "Milton Esco", "first_name" => "Milton", "middle_name" => null, "last_name" => "Esco", "aka" => null, "description" => "Milton Esco participated in the Canton voter-registration campaign.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "levi-jackson" => ["name" => "Levi Jackson", "first_name" => "Levi", "middle_name" => null, "last_name" => "Jackson", "aka" => null, "description" => "Levi Jackson participated in the Canton voter-registration campaign.", "affiliation" => ["Council of Federated Organizations"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "claude-weaver" => ["name" => "Claude Weaver", "first_name" => "Claude", "middle_name" => null, "last_name" => "Weaver", "aka" => null, "description" => "Claude Weaver organized civil-rights work in Canton.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "edward-s-hollander" => ["name" => "Edward S. Hollander", "first_name" => "Edward", "middle_name" => "S.", "last_name" => "Hollander", "aka" => "Ed Hollander; Edward Hollander", "description" => "Edward S. Hollander organized civil-rights work in Canton.", "affiliation" => ["Congress of Racial Equality"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["c-o-chinn" => ["birthdate" => ["year" => 1919, "month" => 9, "day" => 18], "death_date" => ["year" => 1999, "month" => 7, "day" => 19]], "james-collier" => [], "theodis-hewitt" => [], "carole-merritt" => [], "richard-jewett" => [], "patricia-meyers" => [], "martha-jones" => [], "william-veal" => [], "sylvester-lee-palmer" => [], "alma-bosley" => [], "pete-hewitt" => [], "milton-esco" => [], "levi-jackson" => [], "claude-weaver" => [], "edward-s-hollander" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["c-o-chinn" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "james-collier" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 22], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "theodis-hewitt" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "carole-merritt" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 24], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 24], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "richard-jewett" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "patricia-meyers" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "martha-jones" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "william-veal" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "sylvester-lee-palmer" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 28], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 28], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "alma-bosley" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 3]]], "pete-hewitt" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 1]]], "milton-esco" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 1]]], "levi-jackson" => [["arrest_date" => ["year" => 1964, "month" => 1, "day" => 23], "incarceration_date" => ["year" => 1964, "month" => 1, "day" => 23], "release_date" => ["year" => 1964, "month" => 1]]], "claude-weaver" => [["arrest_date" => ["year" => 1964, "month" => 2, "day" => 7], "incarceration_date" => ["year" => 1964, "month" => 2, "day" => 7], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 10]]], "edward-s-hollander" => [["arrest_date" => ["year" => 1964, "month" => 2, "day" => 7], "incarceration_date" => ["year" => 1964, "month" => 2, "day" => 7], "release_date" => ["year" => 1964, "month" => 2, "day" => 21], "sentenced_date" => ["year" => 1964, "month" => 2, "day" => 10]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["c-o-chinn" => ["C. O. Chinn", "C.O. Chinn", "CO Chinn", "C. O. Chinn Sr."], "james-collier" => ["James Collier", "Jim Collier"], "theodis-hewitt" => ["Theodis Hewitt", "Theotis Hewitt", "Theotus Hewitt"], "carole-merritt" => ["Carole Merritt", "Carol Merritt", "Carole Elaine Merritt"], "richard-jewett" => ["Richard Jewett", "Dick Jewett", "Richard Jewitt", "Dick Jewitt"], "patricia-meyers" => ["Patricia Meyers", "Patricia Myers"], "martha-jones" => ["Martha Jones", "Martha James Jones"], "william-veal" => ["William Veal"], "sylvester-lee-palmer" => ["Sylvester Lee Palmer", "Sylvester Palmer"], "alma-bosley" => ["Alma Bosley"], "pete-hewitt" => ["Pete Hewitt", "Peter Hewitt"], "milton-esco" => ["Milton Esco"], "levi-jackson" => ["Levi Jackson"], "claude-weaver" => ["Claude Weaver"], "edward-s-hollander" => ["Edward S. Hollander", "Ed Hollander", "Edward Hollander"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["c-o-chinn" => [["charges" => "Initially disturbing the peace; convicted of intimidating Easter Branch", "sentence" => "February 3, 1964: $500 fine and six months. Appeal release around February 6; approximate day not stored."]], "james-collier" => [["charges" => "Altering the interior of a building without a permit", "sentence" => "February 3, 1964: $100 fine and 15 days city jail. Released February 22 on appeal bond."]], "theodis-hewitt" => [["charges" => "Initially disturbing the peace and intimidating an officer; convicted of intimidating Easter Branch", "sentence" => "February 3, 1964: $500 fine and six months county prison. Released February 21 on appeal bond."]], "carole-merritt" => [["charges" => "Contributing to the delinquency of a minor; publishing libel", "sentence" => "February 3, 1964: $500 and six months on each count. Released February 21 on appeal bond."]], "richard-jewett" => [["charges" => "Publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. February 21 appeal release."]], "patricia-meyers" => [["charges" => "Publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. Around February 5 appeal release."]], "martha-jones" => [["charges" => "Publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. February 21 appeal release."]], "william-veal" => [["charges" => "Publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. February 21 appeal release."]], "sylvester-lee-palmer" => [["charges" => "Publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. February 21 appeal release."]], "alma-bosley" => [["charges" => "Contributing to the delinquency of a minor; publishing libel; disturbing the peace", "sentence" => "February 3, 1964: $500 and six months on each count. Released February 21 on appeal bond."]], "pete-hewitt" => [["charges" => "Distributing leaflets without a permit", "sentence" => "Released after two days without bail. Juvenile hearing February 5, 1964; subsequent good-behavior release. Exact January release day unverified."]], "milton-esco" => [["charges" => "Distributing leaflets without a permit", "sentence" => "Released after two days without bail. Juvenile hearing February 5, 1964; subsequent good-behavior release. Exact January release day unverified."]], "levi-jackson" => [["charges" => "Distributing leaflets without a permit", "sentence" => "Released after two days without bail. Juvenile hearing February 5, 1964; subsequent good-behavior release. Exact January release day unverified."]], "claude-weaver" => [["charges" => "Allegedly intimidating a woman and her child concerning employment at a boycotted store", "sentence" => "February 10, 1964: $500 fine and six months. Released February 21 on appeal bond."]], "edward-s-hollander" => [["charges" => "Allegedly intimidating a woman and her child concerning employment at a boycotted store", "sentence" => "February 10, 1964: $500 fine and six months. Released February 21 on appeal bond."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch345."); }

}
if (($payload["expected_case_count"] ?? null) !== 15 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 15) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B345-OK\n";
'
run "add-affiliation-prisoners" "B345-OK" "$ADD_CODE" || exit 1
echo "Batch 345 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
