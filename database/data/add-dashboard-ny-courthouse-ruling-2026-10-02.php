<?php
// Source metadata verified against Washington Examiner's original October 2, 2026 report.
// Idempotent: preserve an existing MSN, original-source, or exact-title entry.
require '/var/www/NPPC-Website/vendor/autoload.php';
$app=require '/var/www/NPPC-Website/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$title='Federal judge blocks ICE from arrests in New York courthouses';
$link=App\Models\DashboardLink::query()->where('url','like','%AA2dqTga%')->orWhere('url','like','%4751666%')->orWhere('title',$title)->first();
$created=false;
if (!$link) {
$link=App\Models\DashboardLink::create([
 'title'=>$title,
 'url'=>'https://www.msn.com/en-us/news/other/federal-judge-blocks-ice-from-arrests-in-new-york-courthouses/ar-AA2dqTga',
 'source'=>'Washington Examiner via MSN',
 'category'=>'other',
 'lat'=>40.7128,'lng'=>-74.0060,'location_label'=>'New York, New York',
 'published_at'=>'2026-10-02 17:00:00',
]);
$created=true;
}
echo json_encode(['created'=>$created,'entry'=>$link->toArray()],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;