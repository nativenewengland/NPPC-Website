<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\DonateController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/black-panther-party', \App\Http\Controllers\BlackPantherHistoryController::class)
    ->name('black-panther-party');
Route::get('/iww', \App\Http\Controllers\IwwHistoryController::class)->name('iww');

Route::controller(DonateController::class)
    ->group(function () {
        Route::get('/donate-callback', 'callback');
    });

Route::controller(CartController::class)
    ->group(function () {
        Route::get('cart', 'index')->name('cart');
        Route::post('cart/add', 'add');
        Route::post('cart/update', 'update');
        Route::post('cart/remove', 'remove');
        Route::post('cart/checkout', 'checkout');
        Route::get('checkout/success', 'success');
    });

Route::controller(SiteController::class)
    ->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/search', 'search');
        Route::get('history', 'history');
        Route::get('archive', 'archiveRecords');
        Route::get('archive/view/{record}', 'archiveView');
        Route::get('feature-political-prisoner-cost', 'tracker');
        Route::redirect('tracker', '/feature-political-prisoner-cost', 301);
        Route::get('timeline', 'timeline');
        Route::get('annual-report', 'annualReport');
        Route::get('topics/{slug?}', 'topics');
        Route::get('memorial', 'memorial');
        Route::get('icons', 'icons');
        Route::get('museum', 'museum');
        Route::get('thumb/{w}/{path}', 'imageThumb')->where(['w' => '[0-9]+', 'path' => '.*']);
        Route::get('calendar', 'calendar');
        Route::get('birthdays', 'birthdays');
        Route::get('map', 'map');
        Route::get('faq', 'faq');
        Route::get('staff', 'staff');
        Route::get('podcast', 'podcast');
        Route::redirect('shop', '/store', 301);
        Route::get('store', 'store');
        Route::get('store/{slug}', 'storeProduct');
        Route::get('events', 'events');
        Route::get('chapters', 'chapters');
        Route::get('volunteer', 'volunteer');
        Route::get('prisoner-outreach', 'prisonerOutreach');
        Route::get('petitions', 'petitionsIndex');
        Route::get('petition/{slug}', 'petitionPage');
        Route::post('petition/{slug}/sign', 'petitionSign');
        // Deep links into the database's filters. One facet:
        // /database/era/1980s. Several, chained in pairs:
        // /database/era/1980s/ideology/anarchism. Several values in one
        // facet, comma-separated: /database/era/1980s,1990s.
        //
        // Sits above the two-segment article route and the page catch-all,
        // both of which would otherwise 404 it. The pattern spells out the
        // facet keys rather than accepting anything, so a garbage path still
        // 404s instead of silently rendering the unfiltered database; it
        // matches the filter keys the Vue app builds its dropdowns from.
        Route::get('database/{path}', 'databaseFacet')
            ->where('path', '(?:ideology|era|affiliation|state|race|gender)/[^/]+(?:/(?:ideology|era|affiliation|state|race|gender)/[^/]+)*');
        Route::get('prisoner/{slug}', 'prisoner');
        Route::get('author/{slug}', 'author');
        Route::get('state/{slug}', 'state');
        Route::get('board-of-directors', 'boardOfDirectors');
        Route::get('partners', 'partners');
        Route::get('about', 'about');
        Route::get('nppc-quiz', 'nppcQuiz');
        Route::redirect('civic-profile', '/nppc-quiz', 301);
        Route::redirect('dissent-profile', '/nppc-quiz', 301);
        Route::post('nppc-quiz/result', 'nppcQuizResult');
        // Aliases: pages cached before the renames still post here.
        Route::post('dissent-profile/result', 'nppcQuizResult');
        Route::post('civic-profile/result', 'nppcQuizResult');
        Route::post('/sign-up', 'signUp');
        // Category-prefixed article URLs: /{category}/{slug} (e.g. /report/...,
        // /policy-brief/...). Kept after the specific two-segment routes above
        // and before the single-segment page catch-all.
        Route::get('/{type}/{slug}', 'article');
        Route::get('/{slug}', 'page');
    });

Route::controller(FormSubmissionController::class)
    ->group(function () {
        Route::post('/form/{form}', 'submit');
    });
