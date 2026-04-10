<?php

use App\Http\Controllers\CentralController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central App Routes
|--------------------------------------------------------------------------
|
| The central app is the onboarding area where a new tenant signs up for
| the system. The signer becomes the first admin for that tenant.
|
*/

Route::prefix('central')->name('central.')->middleware('central.public')->group(function () {
    Route::get('/', [CentralController::class, 'home'])->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('/register', [CentralController::class, 'create'])->name('register');
        Route::post('/register', [CentralController::class, 'store'])->name('register.store');
    });

    Route::middleware(['auth', 'central.user'])->group(function () {
        Route::get('/dashboard', [CentralController::class, 'dashboard'])->name('dashboard');
        Route::patch('/tenants/{tenant}/approve', [CentralController::class, 'approve'])->name('tenants.approve');
        Route::patch('/tenants/{tenant}', [CentralController::class, 'update'])->name('tenants.update');
        Route::patch('/tenants/{tenant}/rbac', [CentralController::class, 'updateRbac'])->name('tenants.rbac');
        Route::patch('/tenants/{tenant}/activation', [CentralController::class, 'toggleActivation'])->name('tenants.activation');
        Route::patch('/tenants/{tenant}/subscription', [CentralController::class, 'updateSubscription'])->name('tenants.subscription');
        Route::post('/tenants/{tenant}/reset-password', [CentralController::class, 'resetTenantPassword'])->name('tenants.reset-password');
        Route::post('/tenants/{tenant}/workspace-access', [CentralController::class, 'sendWorkspaceAccess'])->name('tenants.workspace-access');
        Route::delete('/tenants/{tenant}', [CentralController::class, 'destroy'])->name('tenants.destroy');
    });
});
