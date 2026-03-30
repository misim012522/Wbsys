<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAccountSettingsController;
use App\Http\Controllers\CustomizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (RBAC: role:tenant_admin, prefix: admin)
|--------------------------------------------------------------------------
*/

Route::middleware('role:tenant_admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::get('/qr', [AdminController::class, 'qrCodes'])->name('qr');
    Route::get('/qr/{office}/image', [AdminController::class, 'qrCodeImage'])->name('qr.image')->middleware('tenant.resource:office');
    Route::get('/serve/{office}', [AdminController::class, 'serveOffice'])->name('serve')->middleware('tenant.resource:office');
    Route::post('/serve/{office}/call-next', [AdminController::class, 'callNext'])->name('call-next')->middleware('tenant.resource:office');
    Route::patch('/queue/{queueEntry}', [AdminController::class, 'updateQueueStatus'])->name('queue.update')->middleware('tenant.resource:queueEntry');
    Route::post('/appointments/{appointment}/accept', [AdminController::class, 'acceptAppointment'])->name('appointments.accept')->middleware('tenant.resource:appointment');
    Route::post('/appointments/{appointment}/complete', [AdminController::class, 'completeAppointment'])->name('appointments.complete')->middleware('tenant.resource:appointment');
    Route::post('/appointments/{appointment}/cancel', [AdminController::class, 'cancelAppointment'])->name('appointments.cancel')->middleware('tenant.resource:appointment');
    Route::get('/offices', [AdminController::class, 'offices'])->name('offices');
    Route::get('/offices/create', [AdminController::class, 'createOffice'])->name('offices.create');
    Route::post('/offices', [AdminController::class, 'storeOffice'])->name('offices.store');
    Route::get('/offices/{office}/edit', [AdminController::class, 'editOffice'])->name('offices.edit')->middleware('tenant.resource:office');
    Route::put('/offices/{office}', [AdminController::class, 'updateOffice'])->name('offices.update')->middleware('tenant.resource:office');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/reports/download', [AdminController::class, 'downloadReport'])->name('reports.download');
    Route::get('/settings', [AdminAccountSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminAccountSettingsController::class, 'update'])->name('settings.update');
    Route::get('/customization', [CustomizationController::class, 'index'])->name('customization.index');
    Route::put('/customization', [CustomizationController::class, 'update'])->name('customization.update');
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::get('/users/pending', [AdminController::class, 'pendingAccounts'])->name('users.pending');
    Route::get('/users/archived', [AdminController::class, 'archivedAccounts'])->name('users.archived');
    Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve')->middleware('tenant.resource:user');
    Route::post('/users/{user}/archive', [AdminController::class, 'archiveUser'])->name('users.archive')->middleware('tenant.resource:user');
    Route::post('/users/{user}/recover', [AdminController::class, 'recoverUser'])->name('users.recover')->middleware('tenant.resource:user');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy')->middleware('tenant.resource:user');
});
