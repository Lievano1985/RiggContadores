<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 👇 Aquí va tu tarea programada
Schedule::command('obligaciones:generar')
    ->monthlyOn(1, '01:05')
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/obligaciones_generadas.log'));
