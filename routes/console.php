<?php

use App\Jobs\DispatchExpansionScrapingJob;
use App\Models\ScrapingExpansion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduling scraping Cardmarket ───────────────────────────────────
// Ogni notte alle 02:00 dispatcha i job di scraping per tutte le espansioni attive
Schedule::call(function () {
    ScrapingExpansion::whereHas('users')->each(function ($expansion) {
        DispatchExpansionScrapingJob::dispatch($expansion->id)
            ->onQueue('scraping');
    });
})->dailyAt('02:00')->name('dispatch-cardmarket-scraping');
