<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Route middleware aliases
        $middleware->alias([
            'resolve.site'      => \App\Http\Middleware\ResolveSite::class,
            'agent.permission'  => \App\Http\Middleware\CheckAgentPermission::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Daily midnight report
        $schedule->command('nutrisport:daily-report')->dailyAt('00:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
