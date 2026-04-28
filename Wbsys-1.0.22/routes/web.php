<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtaUpdateController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TenantUpdateController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\TenantAccountSettingsController;
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
Route::get('/api/tenant-update/status', [TenantUpdateController::class, 'status'])->name('tenant.update.status');
Route::post('/api/tenant-update/apply', [TenantUpdateController::class, 'apply'])->middleware('auth')->name('tenant.update.apply');

// GitHub webhook for automatic release sync
Route::post('/api/github/webhook', [GitHubWebhookController::class, 'handle'])->name('github.webhook');

// Public: end users scan QR and land here (no login)
Route::get('/o/{slug}', [PublicController::class, 'office'])->name('queue.office');
Route::get('/o/{slug}/staff/{userId}', [PublicController::class, 'officeForStaff'])->middleware('signed:relative')->name('queue.office.staff');
Route::post('/o/{slug}/queue', [PublicController::class, 'getQueue'])->name('queue.get');
Route::get('/t/{referenceCode}', [PublicController::class, 'track'])->name('queue.track');

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
    Route::get('/api/session/tenant-status', [ApiController::class, 'tenantSessionStatus'])->name('api.session.tenant-status');
});

Route::middleware(['tenant.required', \App\Http\Middleware\DebugAuth::class, 'auth', 'tenant.context'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings', [TenantAccountSettingsController::class, 'edit'])->name('tenant.settings.edit');
    Route::put('/settings', [TenantAccountSettingsController::class, 'update'])->name('tenant.settings.update');
    Route::get('/support', [SupportChatController::class, 'tenantIndex'])->name('support.tenant.index');
    Route::get('/support/snapshot', [SupportChatController::class, 'tenantSnapshot'])->name('support.tenant.snapshot');
    Route::get('/support/threads/{thread}/messages', function () {
        return redirect('/support');
    });
    Route::post('/support/threads', [SupportChatController::class, 'tenantStoreThread'])->name('support.tenant.threads.store');
    Route::post('/support/threads/{thread}/messages', [SupportChatController::class, 'tenantStoreMessage'])->name('support.tenant.messages.store');

    require __DIR__.'/admin.php';
    require __DIR__.'/office.php';
});
