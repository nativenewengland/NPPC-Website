#!/usr/bin/env bash
# Batch 305: Seventeen verified labor detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-305.sh --dry-run
# sudo -u www-data bash database/data/run-batch-305.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-305.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch305.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 305 || ($payload["expected_count"] ?? null) !== 17 || count($payload["entries"] ?? []) !== 17) {
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
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id", "imprisoned_for_months"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string", "imprisoned_for_months" => "sometimes|integer|min:1"])->validate();
};
foreach ($payload["institutions"] ?? [] as $id => $name) { if (Institution::whereKey($id)->value("name") !== $name) { throw new \RuntimeException("Institution identity mismatch."); } }
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1930s,1940s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["william-j-mackesy" => ["name" => "William J. Mackesy", "first_name" => "William", "middle_name" => null, "last_name" => "Mackesy", "aka" => "William Mackesy; William J. Macksey; William Macksey", "description" => "William J. Mackesy was a shoe-worker strike organizer jailed during the 1937 Lewiston-Auburn shoe strike for violating an injunction against support for the strike.", "affiliation" => ["Congress of Industrial Organizations", "United Shoe Workers of America"], "state" => "Maine", "era" => "1930s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ernest-henry" => ["name" => "Ernest Henry", "first_name" => "Ernest", "middle_name" => null, "last_name" => "Henry", "aka" => null, "description" => "Ernest Henry was a shoe-worker union organizer jailed during the 1937 Lewiston-Auburn shoe strike for violating an injunction against support for the strike.", "affiliation" => ["Congress of Industrial Organizations", "United Shoe Workers of America"], "state" => "Maine", "era" => "1930s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "sidney-grant" => ["name" => "Sidney Grant", "first_name" => "Sidney", "middle_name" => null, "last_name" => "Grant", "aka" => null, "description" => "Sidney Grant was a attorney supporting the shoe-worker strikers jailed during the 1937 Lewiston-Auburn shoe strike for violating an injunction against support for the strike.", "affiliation" => ["Labor movement"], "state" => "Maine", "era" => "1930s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "james-d-o-neil" => ["name" => "James D. O\u{0027}Neil", "first_name" => "James", "middle_name" => null, "last_name" => "O\u{0027}Neil", "aka" => "James O\u{0027}Neil", "description" => "James D. O\u{0027}Neil was a former West Coast CIO publicity director jailed for contempt during the 1941 Harry Bridges deportation hearing after failing to appear when summoned. O\u{0027}Neil said he had been ill; the court rejected that explanation.", "affiliation" => ["Congress of Industrial Organizations"], "state" => "California", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "leslie-wachter" => ["name" => "Leslie Wachter", "first_name" => "Leslie", "middle_name" => null, "last_name" => "Wachter", "aka" => null, "description" => "Leslie Wachter was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "charles-grider" => ["name" => "Charles Grider", "first_name" => "Charles", "middle_name" => null, "last_name" => "Grider", "aka" => null, "description" => "Charles Grider was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "william-riley" => ["name" => "William Riley", "first_name" => "William", "middle_name" => null, "last_name" => "Riley", "aka" => null, "description" => "William Riley was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "george-toteno" => ["name" => "George Toteno", "first_name" => "George", "middle_name" => null, "last_name" => "Toteno", "aka" => "George Totino", "description" => "George Toteno was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "milton-mclean" => ["name" => "Milton McLean", "first_name" => "Milton", "middle_name" => null, "last_name" => "McLean", "aka" => null, "description" => "Milton McLean was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "eddie-alberts" => ["name" => "Eddie Alberts", "first_name" => "Eddie", "middle_name" => null, "last_name" => "Alberts", "aka" => null, "description" => "Eddie Alberts was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "frank-stevens" => ["name" => "Frank Stevens", "first_name" => "Frank", "middle_name" => null, "last_name" => "Stevens", "aka" => null, "description" => "Frank Stevens was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "floyd-hurley" => ["name" => "Floyd Hurley", "first_name" => "Floyd", "middle_name" => null, "last_name" => "Hurley", "aka" => null, "description" => "Floyd Hurley was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "myron-philips" => ["name" => "Myron Philips", "first_name" => "Myron", "middle_name" => null, "last_name" => "Philips", "aka" => "Myron Phillips", "description" => "Myron Philips was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "richard-connell" => ["name" => "Richard Connell", "first_name" => "Richard", "middle_name" => null, "last_name" => "Connell", "aka" => null, "description" => "Richard Connell was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ralph-core" => ["name" => "Ralph Core", "first_name" => "Ralph", "middle_name" => null, "last_name" => "Core", "aka" => null, "description" => "Ralph Core was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "charles-connors" => ["name" => "Charles Connors", "first_name" => "Charles", "middle_name" => null, "last_name" => "Connors", "aka" => null, "description" => "Charles Connors was a Minneapolis WPA striker imprisoned following the 1939 strike against changes to federal work-relief conditions.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "minnie-kohn" => ["name" => "Minnie Kohn", "first_name" => "Minnie", "middle_name" => null, "last_name" => "Kohn", "aka" => null, "description" => "Minnie Kohn was a Minneapolis WPA sewing-project striker jailed after the 1939 strike. She was the only woman among the fourteen sentenced in February 1940 to receive an unsuspended jail term.", "affiliation" => ["Labor movement"], "state" => "Minnesota", "era" => "1940s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["william-j-mackesy" => [], "ernest-henry" => [], "sidney-grant" => [], "james-d-o-neil" => [], "leslie-wachter" => [], "charles-grider" => [], "william-riley" => [], "george-toteno" => [], "milton-mclean" => [], "eddie-alberts" => [], "frank-stevens" => [], "floyd-hurley" => [], "myron-philips" => [], "richard-connell" => [], "ralph-core" => [], "charles-connors" => [], "minnie-kohn" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["william-j-mackesy" => [["incarceration_date" => ["year" => 1937, "month" => 5], "sentenced_date" => ["year" => 1937, "month" => 5]]], "ernest-henry" => [["incarceration_date" => ["year" => 1937, "month" => 5], "sentenced_date" => ["year" => 1937, "month" => 5]]], "sidney-grant" => [["incarceration_date" => ["year" => 1937, "month" => 5], "sentenced_date" => ["year" => 1937, "month" => 5]]], "james-d-o-neil" => [["incarceration_date" => ["year" => 1941, "month" => 4], "sentenced_date" => ["year" => 1941, "month" => 4, "day" => 30]]], "leslie-wachter" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "charles-grider" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "william-riley" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "george-toteno" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "milton-mclean" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "eddie-alberts" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "frank-stevens" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "floyd-hurley" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "myron-philips" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "richard-connell" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "ralph-core" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "charles-connors" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 3], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 3]]], "minnie-kohn" => [["incarceration_date" => ["year" => 1940, "month" => 2, "day" => 10], "sentenced_date" => ["year" => 1940, "month" => 2, "day" => 10]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["william-j-mackesy" => ["William J. Mackesy", "William Mackesy", "William J. Macksey", "William Macksey"], "ernest-henry" => ["Ernest Henry"], "sidney-grant" => ["Sidney Grant"], "james-d-o-neil" => ["James D. O\u{0027}Neil", "James O\u{0027}Neil"], "leslie-wachter" => ["Leslie Wachter"], "charles-grider" => ["Charles Grider"], "william-riley" => ["William Riley"], "george-toteno" => ["George Toteno", "George Totino"], "milton-mclean" => ["Milton McLean"], "eddie-alberts" => ["Eddie Alberts"], "frank-stevens" => ["Frank Stevens"], "floyd-hurley" => ["Floyd Hurley"], "myron-philips" => ["Myron Philips", "Myron Phillips"], "richard-connell" => ["Richard Connell"], "ralph-core" => ["Ralph Core"], "charles-connors" => ["Charles Connors"], "minnie-kohn" => ["Minnie Kohn"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["william-j-mackesy" => [["charges" => "Contempt of court — support for the 1937 shoe strike in violation of an injunction", "sentence" => "Six months in jail. Contemporary reporting confirms actual custody and later release on bail after about two months. The contempt proceedings were dismissed June 30, 1938 for lack of verified petitions. Exact custody endpoints remain unresolved."]], "ernest-henry" => [["charges" => "Contempt of court — support for the 1937 shoe strike in violation of an injunction", "sentence" => "Six months in jail. Contemporary reporting confirms actual custody and later release on bail after about two months. The contempt proceedings were dismissed June 30, 1938 for lack of verified petitions. Exact custody endpoints remain unresolved."]], "sidney-grant" => [["charges" => "Contempt of court — support for the 1937 shoe strike in violation of an injunction", "sentence" => "Six months in jail. Contemporary reporting confirms actual custody and later release on bail after about two months. The contempt proceedings were dismissed June 30, 1938 for lack of verified petitions. Exact custody endpoints remain unresolved."]], "james-d-o-neil" => [["charges" => "Contempt of court — failure to appear as a summoned witness in the Harry Bridges deportation hearing", "sentence" => "Sixty-day county-jail sentence imposed April 30, 1941. Continued custody was ordered despite a stay of execution; the May 3 union newspaper confirms he was serving the term. Actual release and full duration remain unverified."]], "leslie-wachter" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Eight months plus eighteen months probation. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "charles-grider" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Seven months. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "william-riley" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Six months. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "george-toteno" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Six months. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "milton-mclean" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Four months. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "eddie-alberts" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Ninety days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "frank-stevens" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Ninety days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "floyd-hurley" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Ninety days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "myron-philips" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Ninety days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "richard-connell" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Ninety days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "ralph-core" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Sixty days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "charles-connors" => [["charges" => "Federal prosecution arising from the 1939 WPA strike; individual count details unresolved", "sentence" => "Thirty days. The February 1940 union reports confirm actual custody beginning February 3. Exact release and completed duration remain unverified."]], "minnie-kohn" => [["charges" => "Conspiracy arising from the 1939 WPA strike", "sentence" => "Forty-five days in the city workhouse. Contemporary reporting places sentencing and workhouse entry on February 10, 1940; participant Max Geldman later confirmed she served her time. Exact release date remains unresolved."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }

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
echo "B305-OK\n";
'
run "add-affiliation-prisoners" "B305-OK" "$ADD_CODE" || exit 1
echo "Batch 305 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
