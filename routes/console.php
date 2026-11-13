<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('atestados:vencer')->daily();
Schedule::command('boletos:sincronizar')
    ->twiceDaily(...config('cepe.pagamentos.consulta.horarios', [9, 17]))
    ->timezone(config('cepe.fuso_frequencia'))
    ->withoutOverlapping();
Schedule::command('pix:sincronizar')
    ->everyFiveMinutes()
    ->timezone(config('cepe.fuso_frequencia'))
    ->withoutOverlapping();
