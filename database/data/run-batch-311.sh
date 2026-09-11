#!/usr/bin/env bash
# Batch 311: Nine verified environmental activists; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-311.sh --dry-run
# sudo -u www-data bash database/data/run-batch-311.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-311.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch311.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 311 || ($payload["expected_count"] ?? null) !== 9 || count($payload["entries"] ?? []) !== 9) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 311."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1990s,2000s,2010s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["ilse-asplund" => ["name" => "Ilse Asplund", "first_name" => "Ilse", "middle_name" => null, "last_name" => "Asplund", "aka" => null, "description" => "Ilse Asplund is an Arizona environmental activist and community health educator prosecuted with the Arizona Five. She entered prison in October 1991 following a negotiated resolution of the federal case.", "affiliation" => ["Earth First!"], "state" => "Arizona", "era" => "1990s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "erik-bowers-ryberg" => ["name" => "Erik Bowers Ryberg", "first_name" => "Erik", "middle_name" => "Bowers", "last_name" => "Ryberg", "aka" => "Erik Ryberg; Erik B. Ryberg", "description" => "Erik Ryberg participated in the Cove–Mallard forest-defense campaign in Idaho. He was jailed after obstructing a Forest Service vehicle at the protest camp, and his conviction was affirmed on appeal.", "affiliation" => ["Earth First!", "Cove/Mallard Coalition"], "state" => "Idaho", "era" => "1990s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "jennifer-prichard" => ["name" => "Jennifer Prichard", "first_name" => "Jennifer", "middle_name" => null, "last_name" => "Prichard", "aka" => "Jen Prichard", "description" => "Jennifer Prichard was jailed during the Cove–Mallard logging-road campaign in Idaho. After earlier custody, she returned to jail in 1994 for refusing court-ordered restitution to the road-building contractor. The two periods belong to the same original sentence.", "affiliation" => ["Cove/Mallard Coalition"], "state" => "Idaho", "era" => "1990s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "peggy-sue-mcrae" => ["name" => "Peggy Sue McRae", "first_name" => "Peggy", "middle_name" => "Sue", "last_name" => "McRae", "aka" => null, "description" => "Peggy Sue McRae participated in the Cove–Mallard forest-defense campaign. She was jailed in Idaho in 1994 after refusing to pay restitution to the contractor building the logging road.", "affiliation" => ["Cove/Mallard Coalition"], "state" => "Idaho", "era" => "1990s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "panagioti-tsolkas" => ["name" => "Panagioti Tsolkas", "first_name" => "Panagioti", "middle_name" => null, "last_name" => "Tsolkas", "aka" => null, "description" => "Panagioti Tsolkas is an environmental organizer associated with Everglades Earth First! and the Palm Beach County Environmental Coalition. His accounts document jail time during the 1999 WTO protests and following a Florida power-plant blockade.", "affiliation" => ["Everglades Earth First!", "Palm Beach County Environmental Coalition"], "state" => "Florida", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "lynne-purvis" => ["name" => "Lynne Purvis", "first_name" => "Lynne", "middle_name" => null, "last_name" => "Purvis", "aka" => null, "description" => "Lynne Purvis is an environmental activist jailed after an Everglades-area power-plant blockade. Following release, she spoke about the protest, her trial and conditions in Palm Beach County custody.", "affiliation" => ["Everglades Earth First!"], "state" => "Florida", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "willow-cordes-eklund" => ["name" => "Willow Cordes-Eklund", "first_name" => "Willow", "middle_name" => null, "last_name" => "Cordes-Eklund", "aka" => "Willow A. Cordes-Eklund; Willow Amanda Cordez-Eklund", "description" => "Willow Cordes-Eklund participated in a Maine protest against construction of industrial wind turbines in mountain habitat. She was convicted of failing to disperse and jailed in August 2011.", "affiliation" => ["Earth First!"], "state" => "Maine", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "erik-gillard" => ["name" => "Erik Gillard", "first_name" => "Erik", "middle_name" => null, "last_name" => "Gillard", "aka" => "Erik J. Gillard", "description" => "Erik Gillard participated in the 2010 Kibby wind-project protest in Maine. He was convicted of failing to disperse and entered jail following sentencing in June 2011.", "affiliation" => ["Earth First!"], "state" => "Maine", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "stevie-lynn-lowe" => ["name" => "Stevie Lynn Lowe", "first_name" => "Stevie", "middle_name" => "Lynn", "last_name" => "Lowe", "aka" => "Stevie Lowe", "description" => "Stevie Lynn Lowe joined the Barley Barber Swamp protest in Florida. A jury acquitted her of trespassing but convicted her of resisting arrest without violence, resulting in jail time before release on appellate bond.", "affiliation" => ["Everglades Earth First!"], "state" => "Florida", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["ilse-asplund" => [], "erik-bowers-ryberg" => [], "jennifer-prichard" => [], "peggy-sue-mcrae" => [], "panagioti-tsolkas" => [], "lynne-purvis" => [], "willow-cordes-eklund" => [], "erik-gillard" => [], "stevie-lynn-lowe" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["ilse-asplund" => [["sentenced_date" => ["year" => 1991, "month" => 9, "day" => 19], "incarceration_date" => ["year" => 1991, "month" => 10, "day" => 3]]], "erik-bowers-ryberg" => [["arrest_date" => ["year" => 1993, "month" => 8, "day" => 2], "sentenced_date" => ["year" => 1994, "month" => 2], "incarceration_date" => ["year" => 1994, "month" => 2], "release_date" => ["year" => 1994]]], "jennifer-prichard" => [["incarceration_date" => ["year" => 1993], "release_date" => ["year" => 1993]], ["sentenced_date" => ["year" => 1994, "month" => 7, "day" => 12], "incarceration_date" => ["year" => 1994, "month" => 7, "day" => 12]]], "peggy-sue-mcrae" => [["sentenced_date" => ["year" => 1994, "month" => 7, "day" => 7], "incarceration_date" => ["year" => 1994, "month" => 7, "day" => 7]]], "panagioti-tsolkas" => [["incarceration_date" => ["year" => 1999], "release_date" => ["year" => 1999]], ["arrest_date" => ["year" => 2008, "month" => 2], "sentenced_date" => ["year" => 2009, "month" => 2, "day" => 2], "incarceration_date" => ["year" => 2009, "month" => 2, "day" => 2], "release_date" => ["year" => 2009, "month" => 2]]], "lynne-purvis" => [["arrest_date" => ["year" => 2008, "month" => 2], "sentenced_date" => ["year" => 2009, "month" => 2, "day" => 2], "incarceration_date" => ["year" => 2009, "month" => 2, "day" => 2], "release_date" => ["year" => 2009]]], "willow-cordes-eklund" => [["arrest_date" => ["year" => 2010, "month" => 7, "day" => 6], "sentenced_date" => ["year" => 2011, "month" => 6, "day" => 21], "incarceration_date" => ["year" => 2011, "month" => 8, "day" => 25]]], "erik-gillard" => [["arrest_date" => ["year" => 2010, "month" => 7, "day" => 6], "sentenced_date" => ["year" => 2011, "month" => 6, "day" => 21], "incarceration_date" => ["year" => 2011, "month" => 6, "day" => 21]]], "stevie-lynn-lowe" => [["arrest_date" => ["year" => 2009, "month" => 1], "sentenced_date" => ["year" => 2009, "month" => 7, "day" => 23], "incarceration_date" => ["year" => 2009, "month" => 7, "day" => 23]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["ilse-asplund" => ["Ilse Asplund"], "erik-bowers-ryberg" => ["Erik Bowers Ryberg", "Erik Ryberg", "Erik B. Ryberg"], "jennifer-prichard" => ["Jennifer Prichard", "Jen Prichard"], "peggy-sue-mcrae" => ["Peggy Sue McRae"], "panagioti-tsolkas" => ["Panagioti Tsolkas"], "lynne-purvis" => ["Lynne Purvis"], "willow-cordes-eklund" => ["Willow Cordes-Eklund", "Willow A. Cordes-Eklund", "Willow Amanda Cordez-Eklund"], "erik-gillard" => ["Erik Gillard", "Erik J. Gillard"], "stevie-lynn-lowe" => ["Stevie Lynn Lowe", "Stevie Lowe"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["ilse-asplund" => [["charges" => "Reduced federal charge in Arizona Five prosecution — individual statutory count unverified", "sentence" => "Sentenced September 19, 1991; began custody October 3. Contemporary reporting describes thirty days to serve and a suspended balance, followed by house detention and probation. The exact release day and statutory plea count remain unverified; the suspended balance and home detention are not counted as jail time."]], "erik-bowers-ryberg" => [["charges" => "Interference with Forest Service officers — 36 CFR 261.3(a) and (c)", "sentence" => "Six months with four suspended; contemporary reporting and a later letter confirm sixty days served. Sentenced in February 1994 and held in Moscow, Idaho. The Ninth Circuit affirmed the conviction January 6, 1995. Exact custody endpoints remain unverified."]], "jennifer-prichard" => [["charges" => "Cove–Mallard road-blockade conviction — individual count unverified", "sentence" => "The coalition report states that she served the initial thirty-one days of a ninety-day sentence during summer and fall 1993. Individual custody intervals within that year are not resolved. This is the earlier portion, not an additional ninety-day term."], ["charges" => "Probation violation for refusal to pay restitution following Cove–Mallard blockade", "sentence" => "Returned to custody July 12, 1994 to serve the remaining fifty-nine days of the original ninety-day sentence. A contemporaneous statement places her in Idaho County Jail, Grangeville. Exact release remains unverified."]], "peggy-sue-mcrae" => [["charges" => "Refusal to pay court-ordered restitution following Cove–Mallard road-blockade case", "sentence" => "Committed July 7, 1994 for sixty-one days after refusing restitution. The coalition published her statement from Idaho County Jail in August. The exact underlying plea count, any earlier custody episode and the release date remain unverified."]], "panagioti-tsolkas" => [["charges" => "Detention during Seattle WTO protests — released without criminal charges", "sentence" => "His November 2009 retrospective letter states that he spent five days in Seattle jail during the 1999 WTO protests before release without criminal charges. Individual arrest and release days remain unverified."], ["charges" => "Unlawful assembly, trespassing and resisting an officer without violence", "sentence" => "Sentenced and jailed February 2, 2009 following the February 2008 power-plant protest. His February 19 update records thirteen days actually served before release on appellate bond, against a sixty-day sentence. Later appeal outcome remains unverified."]], "lynne-purvis" => [["charges" => "Unlawful assembly, trespassing and resisting an officer without violence", "sentence" => "Sentenced and jailed February 2, 2009 for thirty days after the February 2008 blockade. A co-defendant reported her continued confinement; a March interview confirms release. The prospective February 24 release estimate is not entered as an actual release date."]], "willow-cordes-eklund" => [["charges" => "Failure to disperse — Kibby wind-project blockade", "sentence" => "Sentenced June 21, 2011 to ten days; reporting was stayed until August 25. An August 29 movement notice confirms she was then in Somerset County Jail and that the term began August 25. The earlier sentencing report named Franklin County; the custody notice identifies her actual location. Release day remains unverified."]], "erik-gillard" => [["charges" => "Failure to disperse — Kibby wind-project blockade", "sentence" => "Sentenced June 21, 2011 to ten days in jail and a fine. The contemporary hearing report states that he began serving immediately after the hearing. Actual commitment is verified; the exact release date and completion of the entire term remain unverified."]], "stevie-lynn-lowe" => [["charges" => "Resisting arrest without violence — Barley Barber Swamp protest; acquitted of trespass", "sentence" => "Sentenced July 23, 2009 to ninety days and probation. She remained in custody during the July 27 appellate-bond proceedings; a later movement report confirms bond release. The ninety-day sentence is not recorded as time actually served. Exact release and final appeal outcome remain unverified."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch311."); }

}
if (($payload["expected_case_count"] ?? null) !== 11 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 11) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B311-OK\n";
'
run "add-affiliation-prisoners" "B311-OK" "$ADD_CODE" || exit 1
echo "Batch 311 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
