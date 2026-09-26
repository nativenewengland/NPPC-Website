<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$groups = [
    'China Korean War non-repatriates' => [
        'Clarence Adams', 'Howard Gayle Adams', 'Albert Constant Belhomme',
        'Otho Grayson Bell', 'Richard Gordon', 'William Cowart', 'Rufus Douglas',
        'John Roedel Dunn', 'Andrew Fortuna', 'Lewis Wayne Griggs',
        'Samuel David Hawkins', 'Arlie Pate', 'Scott Rush', 'Lowell Skinner',
        'LaRance Sullivan', 'Richard Tenneson', 'James Veneris', 'Harold Webb',
        'William White', 'Morris Wills', 'Aaron Wilson',
    ],
    'France and Algeria' => [
        'Eldridge Cleaver', 'Kathleen Cleaver', 'Donald Cox', 'Donald L. Cox',
        'Melvin McNair', 'Jean McNair', 'George Brown', 'Joyce Tillerson',
        'George Edward Wright', 'George Wright', 'Willie Roger Holder', 'Roger Holder',
        'Catherine Marie Kerkow', 'Catherine Kerkow', 'Cathy Kerkow',
    ],
    'Cuba' => [
        'Assata Shakur', 'Joanne Chesimard', 'Charlie Hill', 'Nehanda Abiodun',
        'William Morales',
    ],
    'Sweden' => [
        'Terry Whitmore', 'Bruce Stevens Proctor', 'Bruce Proctor', 'Gerry Condon', 'Jerry Condon', 'David Smith',
        'Robert Argento', 'Steve Kinneman', 'William Males', 'Herbert Washington',
    ],
    'Tanzania and Africa' => [
        'Pete O’Neal', 'Pete O\'Neal', 'Charlotte O’Neal', 'Charlotte O\'Neal',
    ],
    'North Korea' => [
        'Charles Robert Jenkins', 'Charles Jenkins', 'James Joseph Dresnok',
        'James Dresnok', 'Larry Allen Abshier', 'Larry Abshier',
        'Jerry Wayne Parrish', 'Jerry Parrish',
    ],
];

$result = [];
foreach ($groups as $group => $names) {
    $rows = [];
    foreach ($names as $name) {
        $query = Prisoner::withoutGlobalScopes();
        $query->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->orWhereRaw('lower(aka) = ?', [mb_strtolower($name)])
            ->orWhereRaw('lower(aka) like ?', ['%; '.mb_strtolower($name).';%'])
            ->orWhereRaw('lower(aka) like ?', [mb_strtolower($name).';%'])
            ->orWhereRaw('lower(aka) like ?', ['%; '.mb_strtolower($name)]);
        $matches = $query->get(['id', 'name', 'aka', 'slug', 'era', 'in_exile', 'currently_in_exile']);
        $rows[$name] = $matches->toArray();
    }
    $result[$group] = $rows;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
