#!/usr/bin/env bash
# Batch 805: separate verified 1930 Foster imprisonment.
# Guard every existing case; add the distinct 1930 custody episode once.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-805.sh [--dry-run]" >&2
    exit 2
fi

nppc_psysh_dir="$(pwd)/storage/framework/psysh"
if ! (umask 077; mkdir -p "$nppc_psysh_dir/config" "$nppc_psysh_dir/data" "$nppc_psysh_dir/runtime"); then
    echo "Cannot prepare PsySH storage; run as the application owner." >&2
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
$payload = json_decode(File::get(base_path("database/data/fixes/batch805.json")), true, 805, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 805 || ($payload["expected_count"] ?? null) !== 1 || count($payload["entries"] ?? []) !== 1) { throw new \RuntimeException("Unexpected batch identity or count."); }
$dry = getenv("NPPC_BATCH_DRY_RUN") === "1";
$same = fn ($a,$b) => $a === null || $b === null ? $a === $b : (string) $a === (string) $b;
for ($attempt = 1; $attempt <= 5; $attempt++) {
try {
if (DB::connection()->getDriverName() === "sqlite") { DB::statement("PRAGMA busy_timeout=15000"); }
$result = DB::transaction(function () use ($payload,$dry,$same) {
    // Reserve the SQLite writer before reading preservation snapshots.
    // This matches no rows and changes no data; it avoids read-to-write upgrade collisions.
    if (! $dry && DB::connection()->getDriverName() === "sqlite") {
        DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);
    }

    $plans = [];
    $allowed = ["judge","institution_id","plead","arrest_date","incarceration_date","release_date","sentenced_date","date_precision","sentence","charges","convicted","imprisoned_for_months","documented_imprisoned_for_days","in_exile_since","in_exile_for_days","end_of_exile","custody_periods"];
    foreach ($payload["entries"] as $entry) {
        $p = Prisoner::withoutGlobalScopes()->findOrFail($entry["prisoner_id"]);
        if ($p->name !== $entry["name"] || $p->slug !== $entry["slug"]) { throw new \RuntimeException("Profile identity changed."); }
        $case = PrisonerCase::withoutGlobalScopes()->find($entry["case_id"]);
        $old = DB::table("prisoner_cases")->where("prisoner_id",$p->id)->whereNotIn("id",array_column($payload["entries"],"case_id"))->orderBy("id")->get()->map(fn($r)=>(array)$r)->all();
        $expected=$entry["expected_cases"]; usort($expected,fn($a,$b)=>strcmp($a["id"],$b["id"]));
        if ($old !== $expected) { throw new \RuntimeException("Case changed since research snapshot"); }
        $changes=$entry["updates"];
        if (array_diff(array_keys($changes),$allowed)) { throw new \RuntimeException("Unexpected field"); }
        $candidate=new PrisonerCase();
        foreach($changes as $f=>$v){$candidate->setAttribute($f,$f==="date_precision"?json_decode($v,true):$v);}
        $candidate->setRelation("prisoner",$p);
        $changes["imprisoned_for_days"]=$candidate->computeImprisonedForDays();
        if(!array_key_exists("expected_days",$entry)||$changes["imprisoned_for_days"]!==$entry["expected_days"]){throw new \RuntimeException("Unexpected elapsed custody");}
        $already=$case!==null;
        if($already){
            if($case->prisoner_id!==$p->id){throw new \RuntimeException("Case owner mismatch");}
            $raw=$case->getAttributes();foreach($changes as $f=>$v){if(!$same($raw[$f]??null,$v)){throw new \RuntimeException("Existing batch case differs");}}
        }
        $plans[] = ["entry"=>$entry,"changes"=>$changes,"already"=>$already];
    }
    $digest = function ($table,$omit) {
        $ctx=hash_init("sha256");
        foreach (DB::table($table)->orderBy("id")->cursor() as $r) {
            if (($omit[$r->id] ?? null) === "entire-row") { continue; }
            $v=(array)$r;
            foreach ($omit[$r->id] ?? [] as $field) { unset($v[$field]); }
            hash_update($ctx,json_encode($v)."\n");
        }
        return hash_final($ctx);
    };
    $omit=[]; foreach($plans as $plan){$omit[$plan["entry"]["case_id"]]="entire-row";}
    $profilesBefore=$digest("prisoners",[]); $casesBefore=$digest("prisoner_cases",$omit);
    $changed=0;
    foreach ($plans as $plan) {
        $entry=$plan["entry"];
        if ($plan["already"]) { echo "Already applied: ",$entry["name"],"\n"; continue; }
        echo ($dry ? "Would update: " : "Updating: "),$entry["name"],"\n";
        if (! $dry) {
            DB::table("prisoner_cases")->insert($plan["changes"]+["id"=>$entry["case_id"],"prisoner_id"=>$entry["prisoner_id"],"created_at"=>now()->format("Y-m-d H:i:s"),"updated_at"=>now()->format("Y-m-d H:i:s")]);
            $after=(array)DB::table("prisoner_cases")->where("id",$entry["case_id"])->first();
            foreach ($plan["changes"] as $field=>$value) { if (! $same($after[$field],$value)) { throw new \RuntimeException("Stored value verification failed."); } }
        }
        $changed++;
    }
    if ($profilesBefore!==$digest("prisoners",[]) || $casesBefore!==$digest("prisoner_cases",$omit)) { throw new \RuntimeException("Unrelated field preservation failed."); }
    echo "Preservation verified: all profile fields and all unrelated case fields unchanged\n";
    return $changed;
});
break;
} catch (\Illuminate\Database\QueryException $error) {
    if ($attempt >= 5 || !str_contains($error->getMessage(), "database is locked")) { throw $error; }
    usleep(500000);
}
}
if (! $dry && $result > 0) { Cache::forget(PrisonerApiController::cacheKey()); }
echo ($dry ? "Would update: " : "Updated: "),$result,"\n";
echo "B805-OK\n";
'
run "add-documented-custody-case" "B805-OK" "$ADD_CODE" || exit 1
echo "Batch 805 complete."
