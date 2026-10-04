<?php
require '/var/www/NPPC-Website/vendor/autoload.php';
$app = require '/var/www/NPPC-Website/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
$items = json_decode(file_get_contents(__DIR__.'/batch133.json'), true, 512, JSON_THROW_ON_ERROR);
$apply = in_array('--apply', $argv, true);
$result = [];
DB::transaction(function () use ($items, $apply, &$result) {
    foreach ($items as $item) {
        $before = $item['before'];
        $row = (array) DB::table('prisoners')->where('id', $before['id'])->first();
        $expectedAlready = $before;
        $expectedAlready['photo'] = $item['photo'];
        if ($row === $expectedAlready && Storage::disk('public')->exists($item['photo']) && hash('sha256', Storage::disk('public')->get($item['photo'])) === $item['sha256']) {
            $result[] = ['name'=>$before['name'], 'slug'=>$before['slug'], 'photo'=>$item['photo'], 'already_applied'=>true];
            continue;
        }
        if ($row !== $before || $row['photo'] !== null) {
            throw new RuntimeException('Profile snapshot changed: '.$before['name']);
        }
        $source = __DIR__.'/'.$item['filename'];
        if (hash_file('sha256', $source) !== $item['sha256'] || !getimagesize($source)) {
            throw new RuntimeException('Image verification failed');
        }
        if (Storage::disk('public')->exists($item['photo'])) {
            throw new RuntimeException('Destination already exists');
        }
        if ($apply) {
            if (!Storage::disk('public')->put($item['photo'], file_get_contents($source))) {
                throw new RuntimeException('Image upload failed');
            }
            DB::table('prisoners')->where('id', $before['id'])->whereNull('photo')->update(['photo'=>$item['photo']]);
            $after = (array) DB::table('prisoners')->where('id', $before['id'])->first();
            $expected = $before;
            $expected['photo'] = $item['photo'];
            if ($after !== $expected || hash('sha256', Storage::disk('public')->get($item['photo'])) !== $item['sha256']) {
                throw new RuntimeException('Unexpected database or image change');
            }
        }
        $result[] = ['name'=>$before['name'], 'slug'=>$before['slug'], 'photo'=>$item['photo']];
    }
});
if ($apply) {
    Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
    Cache::forget('museum:payload:v2');
}
echo json_encode(['applied'=>$apply,'profiles'=>$result,'missing_photos'=>DB::table('prisoners')->whereNull('photo')->count()], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;












