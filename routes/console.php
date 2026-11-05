<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tokens de la API caducados (config/api.php): se borran a diario, 24 h
// después de caducar. Requiere el planificador: `php artisan schedule:run`
// cada minuto (en Windows, una tarea del Programador de tareas).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
