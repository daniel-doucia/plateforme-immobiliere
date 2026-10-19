<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('annonces:expirer')->dailyAt('01:00')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('annonces:rappeler-expiration')->dailyAt('08:00')->withoutOverlapping();
