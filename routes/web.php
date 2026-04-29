<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtaUpdateController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TenantUpdateController;
use App\Http\Controllers\OtaTestController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\TenantAccountSettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ModuleController;

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
Route::post('/api/tenant-update/apply', [TenantUpdateController::class, 'apply'])->middleware(['auth', 'tenant.context'])->name('tenant.update.apply');
Route::resource('modules', ModuleController::class);
// GitHub webhook for automatic release sync
Route::post('/github/webhook', [GitHubWebhookController::class, 'handle'])->name('github.webhook');
Route::post('/api/github/webhook', [GitHubWebhookController::class, 'handle'])->name('github.webhook.api');

// Public: end users scan QR and land here (no login)
Route::get('/o/{slug}', [PublicController::class, 'office'])->name('queue.office');
Route::get('/o/{slug}/staff/{userId}', [PublicController::class, 'officeForStaff'])->name('queue.office.staff');
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

    // Demo announcement feature for update testing
    Route::get('/api/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/api/announcements', [AnnouncementController::class, 'store'])->name('announcements.store')->middleware('can:admin');
    Route::put('/api/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update')->middleware('can:admin');
    Route::delete('/api/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy')->middleware('can:admin');

    // Demo system settings feature for update testing
    Route::get('/api/system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
    Route::get('/api/system-settings/{key}', [SystemSettingController::class, 'show'])->name('system-settings.show');
    Route::post('/api/system-settings', [SystemSettingController::class, 'store'])->name('system-settings.store')->middleware('can:admin');
    Route::put('/api/system-settings/{setting}', [SystemSettingController::class, 'update'])->name('system-settings.update')->middleware('can:admin');
    Route::delete('/api/system-settings/{setting}', [SystemSettingController::class, 'destroy'])->name('system-settings.destroy')->middleware('can:admin');

    // ── OTA Update Demo ──────────────────────────────────────────────────────
    // Used to verify that tenant OTA updates run migrations in isolation.
    // Visit /ota-demo BEFORE update (table missing) and AFTER (table + notes).
    Route::get('/ota-demo', [OtaTestController::class, 'index'])->name('ota.demo.index');
    Route::post('/ota-demo', [OtaTestController::class, 'store'])->name('ota.demo.store');
    Route::post('/ota-demo/announcement', [OtaTestController::class, 'storeAnnouncement'])->name('ota.demo.announcement.store');
    Route::delete('/ota-demo/{id}', [OtaTestController::class, 'destroy'])->name('ota.demo.destroy');

    require __DIR__.'/admin.php';
    require __DIR__.'/office.php';
});

require __DIR__.'/debug.php';
