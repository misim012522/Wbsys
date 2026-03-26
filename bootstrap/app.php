<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [\App\Http\Middleware\ResolveTenant::class]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\HydrateTenantSessionUser::class]);
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'central.user' => \App\Http\Middleware\EnsureCentralUser::class,
            'central.public' => \App\Http\Middleware\EnsureNotTenantWorkspace::class,
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
