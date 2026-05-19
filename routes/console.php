<?php

use Illuminate\Support\Facades\Schedule;

// Verifica campanhas agendadas a cada minuto.
// withoutOverlapping garante que só uma instância roda por vez,
// mesmo se o job anterior demorar mais de 60 s.
Schedule::command('whatstrigger:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
