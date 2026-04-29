<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__.'/../routes/api.php',  // Make sure this line exists

        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => \App\Http\Middleware\SetLocale::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'email.verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,

            'representation' => \App\Http\Middleware\Merchant\MerchantRepresentationMiddleware::class,
            'subscription' => \App\Http\Middleware\Merchant\MerchantSubscribedMiddleware::class,

            'check.agreements' => \App\Http\Middleware\CheckCyclosAgreements::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
