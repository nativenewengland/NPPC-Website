<?php
// Read-only inventory of stored portrait paths and image headers.
require '/var/www/NPPC-Website/vendor/autoload.php';
$app = require '/var/www/NPPC-Website/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('prisoners')->orderBy('sort_order')->get();
$counts = [];
$results = [];
foreach ($rows as $row) {
    $photo = trim((string) $row->photo);
    $status = 'valid_image_header';
    if ($photo === '') $status = 'blank';
    elseif (preg_match('/placeholder|no[-_]?photo|default[-_]?avatar/i', $photo)) $status = 'placeholder_path';
    else {
        $path = Illuminate\Support\Facades\Storage::disk('public')->path($photo);
        if (!is_file($path)) $status = 'missing_asset';
        elseif (!@getimagesize($path)) $status = 'invalid_image_header';
    }
    $counts[$status] = ($counts[$status] ?? 0) + 1;
    $results[] = ['id'=>$row->id,'name'=>$row->name,'slug'=>$row->slug,'photo'=>$row->photo,'status'=>$status];
}
echo json_encode(['checked_at'=>gmdate('c'),'total'=>count($results),'counts'=>$counts,'profiles'=>$results], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
