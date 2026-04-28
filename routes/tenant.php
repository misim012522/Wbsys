<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantAppController;
use App\Http\Controllers\DemoNoteController;
use App\Http\Controllers\DemoFeedbackController;
use App\Http\Controllers\DemoFaqController;
use App\Http\Controllers\DemoSystemLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant App (end-user entrypoint)
|--------------------------------------------------------------------------
|
| The tenant app is the user-facing application — this file provides
| a light-weight entry that proxies to existing public routes.
|
*/

Route::middleware('tenant.required')->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/', [TenantAppController::class, 'home'])->name('home');
    Route::get('/track', [TenantAppController::class, 'lookupTrack'])->name('track.lookup');
    Route::middleware('guest')->group(function () {
        Route::get('/register', [AuthController::class, 'showTenantRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'registerTenantUser'])->name('register.store');
    });

    // Convenience proxies to the existing public routes
    Route::get('/office/{slug}', function ($slug) {
        return redirect()->route('queue.office', ['slug' => $slug]);
    })->name('office');

    Route::get('/track/{referenceCode}', function ($referenceCode) {
        return redirect()->route('queue.track', ['referenceCode' => $referenceCode]);
    })->name('track');

    // Demo Routes
    Route::middleware('auth')->group(function () {
        Route::get('/notes', [DemoNoteController::class, 'index'])->name('notes.index');
        Route::post('/notes', [DemoNoteController::class, 'store'])->name('notes.store');
        
        Route::get('/feedback', [DemoFeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback', [DemoFeedbackController::class, 'store'])->name('feedback.store');
        
        Route::get('/faqs', [DemoFaqController::class, 'index'])->name('faqs.index');
        Route::post('/faqs', [DemoFaqController::class, 'store'])->name('faqs.store');
        
        Route::get('/system-logs', [DemoSystemLogController::class, 'index'])->name('system-logs.index');
        Route::post('/system-logs', [DemoSystemLogController::class, 'store'])->name('system-logs.store');

        Route::get('/help-tickets', [DemoHelpTicketController::class, 'index'])->name('help-tickets.index');
        Route::post('/help-tickets', [DemoHelpTicketController::class, 'store'])->name('help-tickets.store');
    });
});
