<?php

namespace App\Http\Controllers;

use App\Models\Prisoner;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class IwwHistoryController extends Controller
{
    public function __invoke(): View
    {
        $history = json_decode(File::get(resource_path('data/iww-history.json')), true, 512, JSON_THROW_ON_ERROR);
        $profiles = Prisoner::whereIn('slug', $history['profile_order'])
            ->get(['id', 'name', 'slug', 'photo'])->keyBy('slug');
        $topics = Topic::published()->whereIn('slug', array_filter(array_column($history['events'], 'topic')))
            ->get(['id', 'title', 'slug'])->keyBy('slug');
        $recordCount = Prisoner::where(function ($query) {
            $query->whereJsonContains('affiliation', 'Industrial Workers of the World (IWW)')
                ->orWhereJsonContains('affiliation', 'Industrial Workers of the World');
        })->count();

        return view('pages.iww', compact('history', 'profiles', 'topics', 'recordCount'));
    }
}
