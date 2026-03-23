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
        Route::delete('/tenants/{tenant}', [CentralController::class, 'destroy'])->name('tenants.destroy');
    });
});
