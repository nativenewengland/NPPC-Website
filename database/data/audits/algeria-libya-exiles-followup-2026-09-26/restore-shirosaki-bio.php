<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
$profile = Prisoner::withoutGlobalScopes()->where('id', '86c4af51-08e4-451c-9359-d7b7553bdf08')->with('cases')->sole();
$case = $profile->cases->firstWhere('id', 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5');

if ($profile->name !== 'Tsutomu Shirosaki'
    || $profile->in_exile
    || $profile->currently_in_exile
    || ! $case
    || $case->in_exile_since
    || $case->end_of_exile
    || ! str_contains($profile->description, 'Because that period followed release from Japanese custody')) {
    throw new RuntimeException('Shirosaki biography-restore guard failed');
}

$oldBio = 'Tsutomu Shirosaki was a Japanese university student who in the 1970s participated in a string of robberies that aimed to secure funds for Japanese radical groups. Tsutomu was arrested in 1971, and sentenced to ten years in prison for his participation. In 1977 the Japanese Red Army hijacked Japan Airlines Flight 472 and demanded the Japanese government release political prisoners held in Japan in exchange for the passengers. Tsutomu was one of the prisoners who was released and flown to Algeria by the Japanese government to swap him for the hostages. Without a passport or the ability to travel, Tsutomu ended up being settled in Lebanon. On May 14, 1986, two mortar-styled rockets were fired into the U.S. Embassy compound in Jakarta, Indonesia, there were no injuries. A group calling itself the Anti-Imperialist International Brigade (AIIB) claimed responsibility for the attack. Seven weeks later, the Japanese government claimed that they had found a fingerprint of Tsutomu Shirosaki in the hotel room where the rockets were launched from. During the time of the attack, Tsutomu was still in Lebanon and could not leave the country. After the 1993 Oslo Accords, Tsutomu was forced to flee Lebanon due to changing political climate. On September 21, 1996, local police in Nepal arrested Tsutomu after the National Security Agency tapped the phones of his friends to find his location. He was handed over to the FBI and extradited to the United States to stand trial for the Embassy attack. Tsutomu asserted his fingerprint had been planted at the scene from his arrest file which Japanese police had been known to do in order to frame suspects since the 1970s. He was convicted of the attack and sentenced to 30 years in prison.';

if (! $apply) {
    echo json_encode(['ready' => true, 'restore_previous_biography' => true], JSON_PRETTY_PRINT).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-shirosaki-biography-restore-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

DB::transaction(function () use ($profile, $oldBio) {
    $profile->aka = null;
    $profile->description = $oldBio;
    $profile->body = null;
    $profile->save();
});

Cache::forget(PrisonerApiController::cacheKey());

$profile = Prisoner::withoutGlobalScopes()->where('id', '86c4af51-08e4-451c-9359-d7b7553bdf08')->with('cases')->sole();
$case = $profile->cases->firstWhere('id', 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5');
$ok = $profile->description === $oldBio
    && $profile->aka === null
    && $profile->body === null
    && ! $profile->in_exile
    && ! $case->in_exile_since
    && $case->partialDateIso('sentenced_date') === '1998-02-20'
    && $case->partialDateIso('release_date') === '2015-01-16';
if (! $ok) {
    throw new RuntimeException('Post-restore verification failed');
}

echo json_encode(['backup' => $backup, 'restored' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
