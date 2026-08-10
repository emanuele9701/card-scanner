<?php

use App\Console\Commands\FetchPokemonCommand;
use App\Console\Commands\FetchCardTraderCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command(FetchPokemonCommand::class)
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::command(FetchCardTraderCommand::class)
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
