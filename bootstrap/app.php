<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('github:sync-releases')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);
        $middleware->prependToGroup('web', [\App\Http\Middleware\ConfigureSessionCookie::class]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\ResolveTenant::class]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\HydrateTenantSessionUser::class]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\DebugAuth::class]);
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'central.user' => \App\Http\Middleware\EnsureCentralUser::class,
            'central.public' => \App\Http\Middleware\EnsureNotTenantWorkspace::class,
            'stripe.registration.config' => \App\Http\Middleware\EnsureStripeRegistrationConfigured::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
            'tenant.required' => \App\Http\Middleware\EnsureTenantResolved::class,
            'tenant.resource' => \App\Http\Middleware\EnsureResourceBelongsToTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
