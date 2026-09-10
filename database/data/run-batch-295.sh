#!/usr/bin/env bash
# Batch 295: Six missing Anthony Burns rescue defendants with actual jail custody.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-295.sh --dry-run
# sudo -u www-data bash database/data/run-batch-295.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-295.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch295.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 295 || ($payload["expected_count"] ?? null) !== 6 || count($payload["entries"] ?? []) !== 6) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1850s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["albert-gallatin-browne-jr" => ["name" => "Albert Gallatin Browne Jr.", "first_name" => "Albert", "middle_name" => "Gallatin", "last_name" => "Browne", "aka" => "Albert Gallatin Browne; Albert G. Browne Jr.; Albert G. Brown Jr.; Albert J. Brown Jr.", "description" => "Albert Gallatin Browne Jr. was a Salem-born abolitionist and Harvard law student arrested in Boston on May 26, 1854, during the effort to prevent Anthony Burns from being returned to slavery. He was held in Suffolk jail on a murder complaint arising from the death of courthouse guard James Batchelder. The complaint was reduced to riot, and his Harvard class report records admission to bail on June 7; the grand jury found no indictment. His exact physical-release date remains unverified. He later worked as a journalist and as Governor John A. Andrew’s military secretary. He was born February 14, 1835, and died June 24, 1891.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "john-c-cluer" => ["name" => "John C. Cluer", "first_name" => "John", "middle_name" => "C.", "last_name" => "Cluer", "aka" => "John Cluer", "description" => "John C. Cluer was an abolitionist speaker jailed in Boston during the prosecution of people accused of attempting to rescue Anthony Burns from rendition under the Fugitive Slave Act. Arrested on May 27, 1854, he was committed to jail on allegations that included involvement in the death of James Batchelder. A June 7 newspaper report listed him as discharged. His later federal prosecution with other abolitionists ended in 1855; that proceeding does not establish uninterrupted jail time until then. At an antislavery meeting in July 1854, Cluer spoke publicly about his imprisonment and reaffirmed his commitment to abolition.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "john-morrison-burns-rescue" => ["name" => "John Morrison", "first_name" => "John", "middle_name" => null, "last_name" => "Morrison", "aka" => null, "description" => "John Morrison was jailed in Boston in the aftermath of the May 1854 attempt to rescue Anthony Burns from being returned to slavery. Contemporary reporting identifies his arrest and commitment on a murder complaint connected with the death of courthouse guard James Batchelder. The June 7 report still listed Morrison among those held without bail. He was also named in the later federal prosecution of the rescue’s alleged participants, which ended in 1855. These records establish multi-day pretrial custody, but not the exact date of his eventual release or a continuous term extending to the later proceedings.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "walter-bishop-burns-rescue" => ["name" => "Walter Bishop", "first_name" => "Walter", "middle_name" => null, "last_name" => "Bishop", "aka" => "Wesley Bishop", "description" => "Walter Bishop, also recorded as Wesley Bishop, was a Black defendant jailed during Boston’s 1854 prosecution of people accused of participating in the attempted rescue of Anthony Burns. He was arrested on May 26 and committed to jail with the other defendants after the next day’s hearing on a collective murder complaint. The June 7 report listed Bishop among those held without bail. He was named among the defendants represented in the later federal proceedings. His exact release date, total time confined and later life remain unverified; the allegations are not recorded as a conviction.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => "Black", "inmate_number" => null], "john-j-roberts-burns-rescue" => ["name" => "John J. Roberts", "first_name" => "John", "middle_name" => "J.", "last_name" => "Roberts", "aka" => "John Roberts", "description" => "John J. Roberts was arrested in Boston on May 26, 1854, during the attempted rescue of Anthony Burns from rendition to slavery. Contemporary reporting initially accused him of breaking a gas lamp in Court Square. He was then included in the collective murder complaint arising from James Batchelder’s death and committed to jail after the May 27 hearing. The June 7 newspaper report listed Roberts as still awaiting further examination. His eventual disposition, exact release date and total time in custody have not been verified.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "walter-phoenix-burns-rescue" => ["name" => "Walter Phoenix", "first_name" => "Walter", "middle_name" => null, "last_name" => "Phoenix", "aka" => "Walter Phenix; Walter Phinney", "description" => "Walter Phoenix, whose name also appears as Phenix and Phinney in contemporary reporting, was a Black defendant jailed after the May 26, 1854 attempt to rescue Anthony Burns in Boston. Initially accused of riotous conduct, he was included in a collective murder complaint and committed to jail after the May 27 hearing. The June 7 report placed him among the defendants held on $3,000 bail for riot. The available records establish multi-day pretrial detention; they do not establish when bail was posted, his exact release date or the final disposition.", "affiliation" => ["Abolitionist movement"], "state" => "Massachusetts", "era" => "1850s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => "Black", "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["albert-gallatin-browne-jr" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 26]]], "john-c-cluer" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 27], "release_date" => ["year" => 1854]]], "john-morrison-burns-rescue" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 27]]], "walter-bishop-burns-rescue" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 26]]], "john-j-roberts-burns-rescue" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 26]]], "walter-phoenix-burns-rescue" => [["arrest_date" => ["year" => 1854, "month" => 5, "day" => 26]]]];
    $reviewedVitals = ["albert-gallatin-browne-jr" => ["birthdate" => ["year" => 1835, "month" => 2, "day" => 14], "death_date" => ["year" => 1891, "month" => 6, "day" => 24]], "john-c-cluer" => [], "john-morrison-burns-rescue" => [], "walter-bishop-burns-rescue" => [], "john-j-roberts-burns-rescue" => [], "walter-phoenix-burns-rescue" => []];
    $reviewedCases = ["albert-gallatin-browne-jr" => [["charges" => "Murder complaint arising from the attempted rescue of Anthony Burns; reduced to riot", "convicted" => "No conviction; Harvard class report records that the grand jury found no indictment", "sentence" => "Actual pretrial confinement in Suffolk jail following the May 26, 1854 arrest. The June 7 newspaper reports riot bail, and the Harvard class report dates admission to bail to June 7. The exact physical-release day and complete duration are unverified; no prison sentence is recorded."]], "john-c-cluer" => [["charges" => "Murder complaint and alleged riot in the Anthony Burns rescue; later federal obstruction prosecution", "convicted" => "Discharged from initial proceeding by the June 7, 1854 report; later federal prosecution ended in 1855", "sentence" => "Jailed after arrest May 27, 1854; brought up again and committed during the ensuing proceedings. Reported discharged by June 7. Only release year 1854 is stored; the month and day of physical release are unverified. The later federal indictment is not treated as a continuous jail term or a separate proven custody episode."]], "john-morrison-burns-rescue" => [["charges" => "Murder complaint connected with the attempted rescue of Anthony Burns; later federal obstruction prosecution", "convicted" => "Held without bail in the June 7, 1854 report; later federal prosecution ended in 1855; no conviction verified", "sentence" => "Actual jail custody after arrest reported with the May 27, 1854 events; still held without bail in the June 7 report. Exact release and total duration remain unverified. The end of the later federal prosecution is not used as a jail-release date."]], "walter-bishop-burns-rescue" => [["charges" => "Murder complaint arising from the attempted rescue of Anthony Burns; later federal obstruction prosecution", "convicted" => "Held without bail in the June 7, 1854 report; no conviction verified", "sentence" => "Taken into custody May 26, 1854 and committed to jail after the May 27 hearing. The June 7 newspaper reports continued confinement without bail. Exact release and total duration are unverified; no sentence is substituted for actual custody."]], "john-j-roberts-burns-rescue" => [["charges" => "Collective murder complaint after the Anthony Burns rescue attempt; initial allegation of breaking a gas lamp", "convicted" => "Awaiting further examination in the June 7, 1854 report; final disposition unverified", "sentence" => "Actual custody from the May 26 arrest, followed by jail commitment on May 27 and continued proceedings reported June 7. No verified sentence, release date or complete duration. A newspaper prediction of discharge is not treated as an actual discharge."]], "walter-phoenix-burns-rescue" => [["charges" => "Collective murder complaint after the Anthony Burns rescue attempt; later held on riot bail", "convicted" => "Held on $3,000 riot bail in the June 7, 1854 report; final disposition unverified", "sentence" => "Actual custody after arrest May 26, 1854 and jail commitment May 27. The June 7 report records $3,000 bail for riot. Bail being set is not proof that it was posted; exact release and total duration remain unverified."]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["albert-gallatin-browne-jr" => ["source_file" => "database/data/photos/albert-gallatin-browne-jr-harvard.webp", "storage_path" => "prisoners/albert-gallatin-browne-jr-harvard.webp", "sha256" => "c6561398cb8c9fa147da199a65829c2281d274df344e1e50d4882190fae74921", "source_id" => "portrait"], "john-c-cluer" => null, "john-morrison-burns-rescue" => null, "walter-bishop-burns-rescue" => null, "john-j-roberts-burns-rescue" => null, "walter-phoenix-burns-rescue" => null];
    if (($entry["photo"] ?? null) !== $approvedPhotos[$entry["key"]]) { throw new \RuntimeException("Unverified portrait."); }
    if (isset($entry["photo"])) {
        $checkSources([$entry["photo"]["source_id"]]);
        if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }
    }


}
if (($payload["expected_case_count"] ?? null) !== 6 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 6) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B295-OK\n";
'
run "add-affiliation-prisoners" "B295-OK" "$ADD_CODE" || exit 1
echo "Batch 295 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
