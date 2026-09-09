<?php

namespace App\Http\Controllers;

use App\Models\Prisoner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class BlackPantherHistoryController extends Controller
{
    public function __invoke(): View
    {
        $history = json_decode(File::get(resource_path('data/black-panther-history.json')), true, 512, JSON_THROW_ON_ERROR);
        $atlas = json_decode(File::get(resource_path('data/black-panther-locations.json')), true, 512, JSON_THROW_ON_ERROR);
        $profiles = Prisoner::whereIn('slug', $history['profile_order'])
            ->get(['id', 'name', 'slug', 'photo'])->keyBy('slug');

        return view('pages.black-panther-party', compact('history', 'atlas', 'profiles'));
    }
}
