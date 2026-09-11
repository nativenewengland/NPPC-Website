#!/usr/bin/env bash
# Batch 301: Fifteen verified full-book detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-301.sh --dry-run
# sudo -u www-data bash database/data/run-batch-301.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-301.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch301.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 301 || ($payload["expected_count"] ?? null) !== 15 || count($payload["entries"] ?? []) !== 15) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1960s,1980s,2000s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["robert-wollheim" => ["name" => "Robert Wollheim", "first_name" => "Robert", "middle_name" => null, "last_name" => "Wollheim", "aka" => "Bob Wollheim; Robert Douglas Wollheim", "description" => "Robert Wollheim was a Vietnam War draft resister who refused induction in Portland in May 1968. He was imprisoned in 1969, later became a lawyer and served on the Oregon Court of Appeals.", "affiliation" => ["Vietnam War resistance"], "state" => "Oregon", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "steve-woolford" => ["name" => "Steve Woolford", "first_name" => "Steve", "middle_name" => null, "last_name" => "Woolford", "aka" => "Steven Woolford", "description" => "Steve Woolford was a Catholic Worker peace activist. He served six months in 2003 following a Pentagon blood-pouring protest with Steve Baggarly and Bill Frankel-Streit.", "affiliation" => ["Catholic Worker"], "state" => "Virginia", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "mike-miles" => ["name" => "Mike Miles", "first_name" => "Mike", "middle_name" => null, "last_name" => "Miles", "aka" => "Michael Miles", "description" => "Mike Miles was a peace activist who took part in Catholic Worker resistance to war and nuclear weapons. He described imprisonment following a 1981 White House action and a later protest against Project ELF.", "affiliation" => ["Catholic Worker", "Project ELF resistance"], "state" => "District of Columbia", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "hattie-nestel" => ["name" => "Hattie Nestel", "first_name" => "Hattie", "middle_name" => null, "last_name" => "Nestel", "aka" => null, "description" => "Hattie Nestel was an anti-nuclear activist who was jailed in connection with resistance to Trident submarines. Her oral history describes a probation-violation term and her refusal of conditions offered for early release.", "affiliation" => ["Anti-nuclear movement"], "state" => "Connecticut", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "joni-mccoy" => ["name" => "Joni McCoy", "first_name" => "Joni", "middle_name" => null, "last_name" => "McCoy", "aka" => "Joan McCoy", "description" => "Joni McCoy was an anti-nuclear activist whose oral history describes two distinct three-month periods of imprisonment connected with her resistance activities.", "affiliation" => ["Anti-nuclear movement"], "state" => "Michigan", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "tom-karlin" => ["name" => "Tom Karlin", "first_name" => "Tom", "middle_name" => null, "last_name" => "Karlin", "aka" => "Thomas Karlin", "description" => "Tom Karlin was an anti-nuclear activist jailed following a protest at the Bangor Trident base. His oral history describes separation from his family and transfers through several facilities.", "affiliation" => ["Anti-nuclear movement"], "state" => "Washington", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "harry-murray" => ["name" => "Harry Murray", "first_name" => "Harry", "middle_name" => null, "last_name" => "Murray", "aka" => null, "description" => "Harry Murray was a peace activist whose oral history describes custody following antiwar protests in New York, including community confinement with permission to leave for teaching work.", "affiliation" => ["Peace movement"], "state" => "New York", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "genevieve-allen" => ["name" => "Genevieve Allen", "first_name" => "Genevieve", "middle_name" => null, "last_name" => "Allen", "aka" => "Mickey Allen; Genevieve Mickey Allen", "description" => "Genevieve “Mickey” Allen participated in resistance to Trident submarines. She described spending five days in jail after refusing to sign a promise to appear in court.", "affiliation" => ["Anti-nuclear movement"], "state" => "Connecticut", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "kim-wahl" => ["name" => "Kim Wahl", "first_name" => "Kim", "middle_name" => null, "last_name" => "Wahl", "aka" => null, "description" => "Kim Wahl was a peace activist jailed following a protest at Boeing against the murders of Jesuits in El Salvador. Her account distinguishes her own imprisonment from that of her husband, Bill Wahl.", "affiliation" => ["Peace movement"], "state" => "Washington", "era" => null, "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "anne-s-hall" => ["name" => "Anne S. Hall", "first_name" => "Anne", "middle_name" => "S.", "last_name" => "Hall", "aka" => "Anne Hall; Pastor Anne Hall", "description" => "Anne S. Hall was a pastor and anti-nuclear activist. She spent six days in Camden County Jail after a January 1989 protest at the Kings Bay Trident base.", "affiliation" => ["Anti-nuclear movement"], "state" => "Georgia", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "becky-johnson" => ["name" => "Becky Johnson", "first_name" => "Becky", "middle_name" => null, "last_name" => "Johnson", "aka" => "Rebecca Johnson", "description" => "Becky Johnson was a Catholic Worker and School of the Americas protester. She was imprisoned in 2003 and described her experiences in county custody and at Alderson.", "affiliation" => ["Catholic Worker", "School of the Americas Watch (SOA Watch)"], "state" => "Georgia", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "renaye-fewless" => ["name" => "Renaye Fewless", "first_name" => "Renaye", "middle_name" => null, "last_name" => "Fewless", "aka" => null, "description" => "Renaye Fewless was a Los Angeles Catholic Worker intern who participated in a Rampart Police Station protest during the 2000 Democratic National Convention. She spent a week in jail before the charges were dismissed.", "affiliation" => ["Catholic Worker"], "state" => "California", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "marian-mollin" => ["name" => "Marian Mollin", "first_name" => "Marian", "middle_name" => null, "last_name" => "Mollin", "aka" => "Marion Mollin", "description" => "Marian Mollin was a peace activist who later became a historian. She described imprisonment for Honeywell Project resistance and a separate protest at a Massachusetts missile-parts manufacturer.", "affiliation" => ["Honeywell Project", "Atlantic Life Community"], "state" => "Minnesota", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "tina-busch-nema" => ["name" => "Tina Busch-Nema", "first_name" => "Tina", "middle_name" => null, "last_name" => "Busch-Nema", "aka" => "Tina Busch Nema; Christina Busch-Nema", "description" => "Tina Busch-Nema participated in the 2006 School of the Americas protest and was imprisoned at Carswell in Texas in 2007. Her oral history describes prison work and teaching fellow prisoners to fold peace cranes.", "affiliation" => ["School of the Americas Watch (SOA Watch)"], "state" => "Georgia", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "marty-harris" => ["name" => "Marty Harris", "first_name" => "Marty", "middle_name" => null, "last_name" => "Harris", "aka" => "Martin Harris", "description" => "Marty Harris was a Vietnam War draft resister. He entered prison in March 1969 and described serving nineteen months of a forty-two-month sentence.", "affiliation" => ["Vietnam War resistance"], "state" => null, "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["robert-wollheim" => [["incarceration_date" => ["year" => 1969]]], "steve-woolford" => [["incarceration_date" => ["year" => 2003]]], "mike-miles" => [["arrest_date" => ["year" => 1981], "incarceration_date" => ["year" => 1981]], []], "hattie-nestel" => [[]], "joni-mccoy" => [[], []], "tom-karlin" => [[]], "harry-murray" => [[], []], "genevieve-allen" => [[]], "kim-wahl" => [[]], "anne-s-hall" => [["arrest_date" => ["year" => 1989, "month" => 1], "incarceration_date" => ["year" => 1989, "month" => 1]]], "becky-johnson" => [["sentenced_date" => ["year" => 2003], "incarceration_date" => ["year" => 2003]]], "renaye-fewless" => [["arrest_date" => ["year" => 2000], "incarceration_date" => ["year" => 2000]]], "marian-mollin" => [["arrest_date" => ["year" => 1983]], []], "tina-busch-nema" => [["arrest_date" => ["year" => 2006], "sentenced_date" => ["year" => 2007, "month" => 1, "day" => 29], "incarceration_date" => ["year" => 2007, "month" => 3]]], "marty-harris" => [["incarceration_date" => ["year" => 1969, "month" => 3]]]];
    $reviewedVitals = ["robert-wollheim" => ["death_date" => ["year" => 2019, "month" => 9, "day" => 21]], "steve-woolford" => [], "mike-miles" => [], "hattie-nestel" => [], "joni-mccoy" => [], "tom-karlin" => [], "harry-murray" => [], "genevieve-allen" => [], "kim-wahl" => [], "anne-s-hall" => [], "becky-johnson" => [], "renaye-fewless" => [], "marian-mollin" => [], "tina-busch-nema" => [], "marty-harris" => []];
    $reviewedNames = ["robert-wollheim" => ["Robert Wollheim", "Bob Wollheim", "Robert Douglas Wollheim"], "steve-woolford" => ["Steve Woolford", "Steven Woolford"], "mike-miles" => ["Mike Miles", "Michael Miles"], "hattie-nestel" => ["Hattie Nestel"], "joni-mccoy" => ["Joni McCoy", "Joan McCoy"], "tom-karlin" => ["Tom Karlin", "Thomas Karlin"], "harry-murray" => ["Harry Murray"], "genevieve-allen" => ["Genevieve Allen", "Mickey Allen", "Genevieve Mickey Allen"], "kim-wahl" => ["Kim Wahl"], "anne-s-hall" => ["Anne S. Hall", "Anne Hall", "Pastor Anne Hall"], "becky-johnson" => ["Becky Johnson", "Rebecca Johnson"], "renaye-fewless" => ["Renaye Fewless"], "marian-mollin" => ["Marian Mollin", "Marion Mollin"], "tina-busch-nema" => ["Tina Busch-Nema", "Tina Busch Nema", "Christina Busch-Nema"], "marty-harris" => ["Marty Harris", "Martin Harris"]];
    $reviewedCases = ["robert-wollheim" => [["charges" => "Refusal of military induction", "sentence" => "Eighteen-month sentence for refusing induction. His oral history describes less than five months in custody in 1969, including Los Angeles County Jail and Safford, Arizona. Exact custody endpoints remain unverified."]], "steve-woolford" => [["charges" => "Pentagon blood-pouring protest; exact statutory charge unverified", "sentence" => "Six-month sentence, explicitly described as served in two facilities: a regional jail in Virginia and a federal facility at Butner. Transfers belong to the same term; exact admission and release days remain unverified.", "imprisoned_for_months" => 6]], "mike-miles" => [["charges" => "Failure to quit and depredation of government property", "sentence" => "Served six months in the District of Columbia jail following a Holy Week 1981 White House blood-pouring action. Exact custody endpoints remain unverified.", "imprisoned_for_months" => 6], ["charges" => "Project ELF protest; exact statutory charge unverified", "sentence" => "Described a separate period of a couple of weeks in Ashland County Jail following Project ELF resistance with Ladon Sheets. Exact year, endpoints and sentence remain unverified."]], "hattie-nestel" => [["charges" => "Probation violation connected with Trident protest; trespass and resisting-arrest context", "sentence" => "Forty-five-day sentence. A visit with two weeks remaining and her account of refusing conditional release establish actual multi-day custody. The exact year, facility and total days served remain unverified."]], "joni-mccoy" => [["charges" => "Wurtsmith Air Force Base resistance; exact statutory charge unverified", "sentence" => "Served three months in federal custody at Lexington, Kentucky, following Wurtsmith Air Force Base resistance. Exact year and custody endpoints remain unverified.", "imprisoned_for_months" => 3], ["charges" => "Anti-nuclear resistance; exact statutory charge unverified", "sentence" => "A separate three-month term at Bay County Jail, Michigan, described after the Lexington imprisonment. Exact action, year and custody endpoints remain unverified.", "imprisoned_for_months" => 3]], "tom-karlin" => [["charges" => "Bangor Trident-base protest; exact statutory charge unverified", "sentence" => "Described roughly four months away from home, through King County Jail, Los Angeles County Jail and the federal camp at Boron. His estimated transfer periods do not establish an exact total. The year and custody endpoints remain unverified."]], "harry-murray" => [["charges" => "Seneca Army Depot protest during the Gulf War; exact statutory charge unverified", "sentence" => "Ninety-day community-confinement sentence at the Salvation Army Community Corrections Center in Rochester, with daytime release for college teaching. This was residential community custody. Exact year and endpoints remain unverified."], ["charges" => "Albany armory protest against aid to the Contras; exact statutory charge unverified", "sentence" => "Served fifteen days after refusing to pay a fine for an earlier Albany armory protest. Exact year and custody endpoints remain unverified."]], "genevieve-allen" => [["charges" => "Trident protest; held after declining a promise to appear", "sentence" => "Spent five days in jail and subsequently received a time-served sentence. The exact action date, year and facility remain unverified."]], "kim-wahl" => [["charges" => "Boeing sidewalk protest; exact statutory charge unverified", "sentence" => "Served twenty-five days in King County Jail after refusing to pay a fine. Exact protest and custody dates remain unverified."]], "anne-s-hall" => [["charges" => "Kings Bay Trident-base protest; exact statutory charge unverified", "sentence" => "Served six days in Camden County Jail, Georgia. The January 1989 arrest month is supported by the book photograph caption; no exact arrest or release day is established."]], "becky-johnson" => [["charges" => "School of the Americas protest; exact statutory charge unverified", "sentence" => "Sentenced to six months in winter 2003 and immediately jailed. She described about a month in county custody before transfer to Alderson. Exact custody endpoints and completion of the full sentence remain unverified."]], "renaye-fewless" => [["charges" => "Rampart Police Station protest; exact statutory charge unverified", "sentence" => "Held for one full week after the 2000 Democratic National Convention protest in Los Angeles. Charges were dismissed. Exact arrest and release days remain unverified."]], "marian-mollin" => [["charges" => "Honeywell blockade; exact statutory charge unverified", "sentence" => "Served forty-eight hours after her second Honeywell prosecution, associated with the 1983 blockade. Her first Honeywell trial ended in acquittal. Exact custody dates remain unverified."], ["charges" => "Protest at a Massachusetts missile-parts manufacturer; exact statutory charge unverified", "sentence" => "Served thirty days after crossing the line at the manufacturer. The exact company, year and custody endpoints remain unverified; this was separate from the Honeywell case."]], "tina-busch-nema" => [["charges" => "School of the Americas protest; exact statutory charge unverified", "sentence" => "Two-month sentence imposed January 29, 2007. Entered Carswell at the end of March and was interviewed after release in 2007. Exact entry and release days and full time served remain unverified."]], "marty-harris" => [["charges" => "Refusal of military induction", "sentence" => "Forty-two-month sentence; nineteen months actually served according to his oral history. Entered prison in March 1969. Institution and exact release date remain unverified.", "imprisoned_for_months" => 19]]];
    if ($entry["match_names"] !== $reviewedNames[$entry["key"]] || $entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed identity, case or date."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait included in this custody batch."); }


}
if (($payload["expected_case_count"] ?? null) !== 19 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 19) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B301-OK\n";
'
run "add-affiliation-prisoners" "B301-OK" "$ADD_CODE" || exit 1
echo "Batch 301 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
