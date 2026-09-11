#!/usr/bin/env bash
# Batch 336: Twelve verified Balayan military detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-336.sh --dry-run
# sudo -u www-data bash database/data/run-batch-336.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-336.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch336.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 336 || ($payload["expected_count"] ?? null) !== 12 || count($payload["entries"] ?? []) !== 12) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 336."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1900s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["ramon-mortel" => ["name" => "Ramon Mortel", "first_name" => "Ramon", "middle_name" => null, "last_name" => "Mortel", "aka" => null, "description" => "Ramon Mortel was recorded by the US Army as a resident of Pasal detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "gregorio-mortel" => ["name" => "Gregorio Mortel", "first_name" => "Gregorio", "middle_name" => null, "last_name" => "Mortel", "aka" => null, "description" => "Gregorio Mortel was recorded by the US Army as a resident of Taal detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "domingo-macuha" => ["name" => "Domingo Macuha", "first_name" => "Domingo", "middle_name" => null, "last_name" => "Macuha", "aka" => null, "description" => "Domingo Macuha was recorded by the US Army as a resident of Bauan detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "rufino-macuha" => ["name" => "Rufino Macuha", "first_name" => "Rufino", "middle_name" => null, "last_name" => "Macuha", "aka" => null, "description" => "Rufino Macuha was recorded by the US Army as a resident of Bauan detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "brigido-mi-a" => ["name" => "Brigido Miña", "first_name" => "Brigido", "middle_name" => null, "last_name" => "Miña", "aka" => "Brigido Mina", "description" => "Brigido Miña was recorded by the US Army as a resident of Bauan detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "francisco-cadacio" => ["name" => "Francisco Cadacio", "first_name" => "Francisco", "middle_name" => null, "last_name" => "Cadacio", "aka" => null, "description" => "Francisco Cadacio was recorded by the US Army as a resident of Pinagkurusan detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "juan-suarez" => ["name" => "Juan Suarez", "first_name" => "Juan", "middle_name" => null, "last_name" => "Suarez", "aka" => null, "description" => "Juan Suarez was recorded by the US Army as a resident of Pinagkurusan detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "sinforoso-de-leon" => ["name" => "Sinforoso de Leon", "first_name" => "Sinforoso", "middle_name" => "de", "last_name" => "Leon", "aka" => null, "description" => "Sinforoso de Leon was recorded by the US Army as a resident of Tingloy detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "andres-mendoza" => ["name" => "Andres Mendoza", "first_name" => "Andres", "middle_name" => null, "last_name" => "Mendoza", "aka" => null, "description" => "Andres Mendoza was recorded by the US Army as a resident of Taal detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "gregorio-hernandez" => ["name" => "Gregorio Hernandez", "first_name" => "Gregorio", "middle_name" => null, "last_name" => "Hernandez", "aka" => null, "description" => "Gregorio Hernandez was recorded by the US Army as a resident of Tingloy detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "francisco-endozo" => ["name" => "Francisco Endozo", "first_name" => "Francisco", "middle_name" => null, "last_name" => "Endozo", "aka" => "Francisco Endoza", "description" => "Francisco Endozo was recorded by the US Army as a resident of Taal detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as a suspected insurgent. The register documents confinement for several weeks followed by release.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "paulino-evangelista" => ["name" => "Paulino Evangelista", "first_name" => "Paulino", "middle_name" => null, "last_name" => "Evangelista", "aka" => "Paulin Evangelista", "description" => "Paulino Evangelista was recorded by the US Army as a resident of Papaya detained at Balayan, Batangas, during the Philippine-American War. The military register classified this detainee as an insurgent tax collector. The register records death in the post hospital in January 1901.", "affiliation" => ["Philippine-American War"], "state" => "Philippines", "era" => "1900s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["ramon-mortel" => [], "gregorio-mortel" => [], "domingo-macuha" => [], "rufino-macuha" => [], "brigido-mi-a" => [], "francisco-cadacio" => [], "juan-suarez" => [], "sinforoso-de-leon" => [], "andres-mendoza" => [], "gregorio-hernandez" => [], "francisco-endozo" => [], "paulino-evangelista" => ["death_date" => ["year" => 1901, "month" => 1, "day" => 9]]];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["ramon-mortel" => [["arrest_date" => ["year" => 1900, "month" => 10, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 10, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "gregorio-mortel" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 22]]], "domingo-macuha" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "rufino-macuha" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "brigido-mi-a" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "francisco-cadacio" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 31]]], "juan-suarez" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "sinforoso-de-leon" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 26]]], "andres-mendoza" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 20]]], "gregorio-hernandez" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1900, "month" => 12, "day" => 13]]], "francisco-endozo" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "release_date" => ["year" => 1901, "month" => 1, "day" => 2]]], "paulino-evangelista" => [["arrest_date" => ["year" => 1900, "month" => 11, "day" => 27], "incarceration_date" => ["year" => 1900, "month" => 11, "day" => 27], "death_in_custody_date" => ["year" => 1901, "month" => 1, "day" => 9]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["ramon-mortel" => ["Ramon Mortel"], "gregorio-mortel" => ["Gregorio Mortel"], "domingo-macuha" => ["Domingo Macuha"], "rufino-macuha" => ["Rufino Macuha"], "brigido-mi-a" => ["Brigido Miña", "Brigido Mina"], "francisco-cadacio" => ["Francisco Cadacio"], "juan-suarez" => ["Juan Suarez"], "sinforoso-de-leon" => ["Sinforoso de Leon"], "andres-mendoza" => ["Andres Mendoza"], "gregorio-hernandez" => ["Gregorio Hernandez"], "francisco-endozo" => ["Francisco Endozo", "Francisco Endoza"], "paulino-evangelista" => ["Paulino Evangelista", "Paulin Evangelista"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["ramon-mortel" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released by the commanding officer on the request and responsibility of the president of Balayan. The oath and surgeon wording on this row is struck through and is not asserted. No individual criminal judgment or sentence is recorded in the reviewed row."]], "gregorio-mortel" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released at Major Parker’s request. No individual criminal judgment or sentence is recorded in the reviewed row."]], "domingo-macuha" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released on oath at the request and responsibility of Mariano Ramos; the original mention of the president of Balayan is crossed out. No individual criminal judgment or sentence is recorded in the reviewed row."]], "rufino-macuha" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Ditto marks carry the oath and Mariano Ramos disposition from the preceding row. No individual criminal judgment or sentence is recorded in the reviewed row."]], "brigido-mi-a" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Ditto marks carry the oath and Mariano Ramos disposition from the preceding rows. No individual criminal judgment or sentence is recorded in the reviewed row."]], "francisco-cadacio" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released by the commanding officer after an oath; the January entry repeats this December31 disposition and is not a new arrest. No individual criminal judgment or sentence is recorded in the reviewed row."]], "juan-suarez" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released on oath at the request of the president of Balayan. No individual criminal judgment or sentence is recorded in the reviewed row."]], "sinforoso-de-leon" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released on oath at the request and responsibility of the president of Balayan. The clerk corrected the reversed name order in the original row. No individual criminal judgment or sentence is recorded in the reviewed row."]], "andres-mendoza" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released on oath under the patronage of the president of Balayan. No individual criminal judgment or sentence is recorded in the reviewed row."]], "gregorio-hernandez" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Released on account of sickness by the commanding officer. No individual criminal judgment or sentence is recorded in the reviewed row."]], "francisco-endozo" => [["charges" => "Military detention as suspected insurgent; no criminal charge recorded", "sentence" => "Continued in the January roster, then released on oath at the request and responsibility of the president of Balayan. Endozo/Endoza spellings are treated as one identity. No individual criminal judgment or sentence is recorded in the reviewed row."]], "paulino-evangelista" => [["charges" => "Military detention as alleged insurgent tax collector; no criminal charge recorded", "sentence" => "The January disposition records death in the post hospital on January9,1901 and burial in Balayan; it does not record release. No individual criminal judgment or sentence is recorded in the reviewed row."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch336."); }

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
echo "B336-OK\n";
'
run "add-affiliation-prisoners" "B336-OK" "$ADD_CODE" || exit 1
echo "Batch 336 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
