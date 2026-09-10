#!/usr/bin/env bash
# Batch 284: Four missing prison organizers; four documented segregation cases.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-284.sh --dry-run
# sudo -u www-data bash database/data/run-batch-284.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-284.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch284.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 284 || ($payload["expected_count"] ?? null) !== 4 || count($payload["entries"] ?? []) !== 4) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:2000s,2010s", "prisoner.in_custody" => "required|boolean|accepted", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka", "website", "inmate_number"])) { throw new \RuntimeException("Unexpected profile field."); }
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
    $expected = ["michael-zaharibu-dorrough" => ["name" => "Michael Zaharibu Dorrough", "affiliation" => ["Prisoner Human Rights Movement"], "state" => "California", "era" => "2000s", "lat" => 36.1, "lng" => -119.56, "in_custody" => true, "released" => false, "website" => "https://zaharibu.wordpress.com/", "inmate_number" => "D83611"], "todd-lewis-ashker" => ["name" => "Todd Lewis Ashker", "affiliation" => ["Prisoner Human Rights Movement", "Short Corridor Collective"], "state" => "California", "era" => "2010s", "lat" => 35.28, "lng" => -120.66, "in_custody" => true, "released" => false, "website" => "https://toddashker.wordpress.com/", "inmate_number" => "C58191"], "mutope-duguma" => ["name" => "Mutope Duguma", "affiliation" => ["Prisoner Human Rights Movement"], "state" => "California", "era" => "2000s", "lat" => 34.7, "lng" => -118.14, "in_custody" => true, "released" => false, "website" => null, "inmate_number" => "D05996"], "joka-heshima-jinsai" => ["name" => "Joka Heshima Jinsai", "affiliation" => ["Prisoner Human Rights Movement", "Autonomous Infrastructure Mission"], "state" => "California", "era" => "2000s", "lat" => 35.77, "lng" => -119.25, "in_custody" => true, "released" => false, "website" => null, "inmate_number" => "J38283"]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected identity, affiliation, location, support site or custody status."); } }
    $reviewedDates = ["michael-zaharibu-dorrough" => [[]], "todd-lewis-ashker" => [[]], "mutope-duguma" => [[]], "joka-heshima-jinsai" => [[]]];
    $reviewedVitals = ["michael-zaharibu-dorrough" => ["birthdate" => ["year" => 1954, "month" => 1, "day" => 27]], "todd-lewis-ashker" => ["birthdate" => ["year" => 1963, "month" => 7, "day" => 5]], "mutope-duguma" => ["birthdate" => ["year" => 1966, "month" => 8, "day" => 26]], "joka-heshima-jinsai" => []];
    $reviewedCases = ["michael-zaharibu-dorrough" => [["charges" => "Continued administrative segregation after a disputed 2006 gang-status review", "sentence" => "In the 2012 UN petition, Dorrough said a 2006 review extended his SHU confinement using political and historical books as evidence. The submission describes actual ongoing isolation years after that review. His later published letters also document confinement at Corcoran SHU during 2013 and 2014. The claimed political basis is his account, not a judicial finding. This was prison classification within an existing sentence, not a new criminal conviction. No exact end date is established for this episode.", "institution_id" => "a470fa1a-4cfc-4b15-b5cc-b40560c7a8c7"]], "todd-lewis-ashker" => [["charges" => "Retaliatory administrative segregation connected to prison-rights litigation and organizing", "sentence" => "California’s March 2025 Assembly hearing agenda reports that, on January 5, 2023, the court found CDCR had retaliated against Ashker by holding him in administrative segregation since 2017 despite approval for general-population housing. It directed the parties to discuss remedies. This documents years of actual confinement, not a proposed penalty. The final segregation exit date remains unverified. Later appellate proceedings concerning broader settlement monitoring are distinct from this historical finding; no present solitary-confinement status is inferred."]], "mutope-duguma" => [["charges" => "Prolonged SHU confinement under a disputed Black Guerrilla Family associate classification", "sentence" => "Duguma’s 2012 UN petition account and correspondence published by Solitary Watch describe more than a decade in segregation. He denied gang involvement and said officials used his political beliefs and another prisoner’s claim to justify isolation. The sources establish prolonged confinement, but the political motive remains attributed to him. Reports differ on the full historical duration, so no exact continuous term or ongoing solitary-confinement counter is assigned. The separate 2012 mail-censorship victory is not a segregation release order.", "institution_id" => "4d17ee62-74b8-4c04-9e72-a9d58580d5a8"]], "joka-heshima-jinsai" => [["charges" => "Prolonged SHU confinement based on disputed political-material and association evidence", "sentence" => "Denham’s account in the 2012 UN petition describes about eleven years of segregation and says officials relied on political artwork, a prison-news article and association evidence. A later clemency campaign also reports prolonged isolation connected to his advocacy. These are attributed accounts, not a finding that every classification allegation was false. The reviewed sources do not establish a reliable exact endpoint for this episode. No parole estimate or transfer out of isolation is entered as a prison release date.", "institution_id" => "a470fa1a-4cfc-4b15-b5cc-b40560c7a8c7"]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["michael-zaharibu-dorrough" => ["source_file" => "database/data/photos/michael-zaharibu-dorrough-support-site.jpg", "storage_path" => "prisoners/michael-zaharibu-dorrough-support-site.jpg", "sha256" => "903f31bb2a257f8787878183cbd2693f9cc14b4b5f5caf082f4f16ab8ac309cc", "source_id" => "dorrough_support"], "todd-lewis-ashker" => ["source_file" => "database/data/photos/todd-ashker-ccr-interview-2015.jpg", "storage_path" => "prisoners/todd-ashker-ccr-interview-2015.jpg", "sha256" => "639c093b3743752f3c9547e28254df4b2f9767626dcd8f66be0757ef7250ca7e", "source_id" => "ashker_photo"], "mutope-duguma" => null, "joka-heshima-jinsai" => null];
    if (($entry["photo"] ?? null) !== $approvedPhotos[$entry["key"]]) { throw new \RuntimeException("Unverified portrait."); }
    if (isset($entry["photo"])) {
        $checkSources([$entry["photo"]["source_id"]]);
        if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }
    }

}
if (($payload["expected_case_count"] ?? null) !== 4 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 4) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B284-OK\n";
'
run "add-affiliation-prisoners" "B284-OK" "$ADD_CODE" || exit 1
echo "Batch 284 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
