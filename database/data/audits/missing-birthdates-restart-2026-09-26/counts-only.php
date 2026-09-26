<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$query = Illuminate\Support\Facades\DB::table('prisoners')->whereNull('birthdate');
$byEra = (clone $query)
    ->selectRaw("coalesce(era, 'unspecified') as era, count(*) as total")
    ->groupBy('era')
    ->orderBy('era')
    ->pluck('total', 'era');

echo json_encode([
    'generated_at' => now()->toIso8601String(),
    'total_missing_birthdate' => $query->count(),
    'counts_by_era' => $byEra,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
