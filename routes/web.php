<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtaUpdateController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Home
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Example page for toast notifications and real-time refresh (remove in production)
Route::get('/example-toasts', function () {
    return view('example-toasts');
})->middleware('auth')->name('example.toasts');

// Support & OTA updates
Route::get('/api/ota/check', [OtaUpdateController::class, 'check'])->name('ota.check');

// Public: end users scan QR and land here (no login)
Route::get('/o/{slug}', [PublicController::class, 'office'])->name('queue.office');
Route::post('/o/{slug}/queue', [PublicController::class, 'getQueue'])->name('queue.get');
Route::get('/t/{referenceCode}', [PublicController::class, 'track'])->name('queue.track');
Route::post('/o/{slug}/book', [PublicController::class, 'bookAppointment'])->name('queue.book');

// Central and tenant app entry points
require __DIR__.'/central.php';
require __DIR__.'/tenant.php';
/*
|--------------------------------------------------------------------------
| Guest Auth (login, register, password reset)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Email Verification (guest-accessible link)
|--------------------------------------------------------------------------
*/

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| API Routes (authenticated)
|--------------------------------------------------------------------------
*/

require __DIR__.'/api.php';

/*
|--------------------------------------------------------------------------
| Authenticated Routes (dashboard, admin, office)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['tenant.required', 'auth', 'tenant.context'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    require __DIR__.'/admin.php';
    require __DIR__.'/office.php';
});
