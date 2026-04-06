<?php

use App\Http\Controllers\AdminAccountSettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminRbacController;
use App\Http\Controllers\CustomizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (RBAC: role:tenant_admin, prefix: admin)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard')->middleware('role:tenant_admin');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile')->middleware('role:tenant_admin');
    Route::get('/qr', [AdminController::class, 'qrCodes'])->name('qr')->middleware('role:tenant_admin');
    Route::get('/qr/{office}/image', [AdminController::class, 'qrCodeImage'])->name('qr.image')->middleware(['role:tenant_admin', 'tenant.resource:office']);
    Route::get('/serve/{office}', [AdminController::class, 'serveOffice'])->name('serve')->middleware(['role:tenant_admin', 'tenant.resource:office']);
    Route::post('/serve/{office}/call-next', [AdminController::class, 'callNext'])->name('call-next')->middleware(['role:tenant_admin', 'tenant.resource:office']);
    Route::patch('/queue/{queueEntry}', [AdminController::class, 'updateQueueStatus'])->name('queue.update')->middleware(['role:tenant_admin', 'tenant.resource:queueEntry']);
    Route::post('/appointments/{appointment}/accept', [AdminController::class, 'acceptAppointment'])->name('appointments.accept')->middleware(['role:tenant_admin', 'tenant.resource:appointment']);
    Route::post('/appointments/{appointment}/complete', [AdminController::class, 'completeAppointment'])->name('appointments.complete')->middleware(['role:tenant_admin', 'tenant.resource:appointment']);
    Route::post('/appointments/{appointment}/cancel', [AdminController::class, 'cancelAppointment'])->name('appointments.cancel')->middleware(['role:tenant_admin', 'tenant.resource:appointment']);
    Route::get('/offices', [AdminController::class, 'offices'])->name('offices')->middleware('role:tenant_admin');
    Route::get('/offices/create', [AdminController::class, 'createOffice'])->name('offices.create')->middleware('role:tenant_admin');
    Route::post('/offices', [AdminController::class, 'storeOffice'])->name('offices.store')->middleware('role:tenant_admin');
    Route::get('/offices/{office}/edit', [AdminController::class, 'editOffice'])->name('offices.edit')->middleware(['role:tenant_admin', 'tenant.resource:office']);
    Route::put('/offices/{office}', [AdminController::class, 'updateOffice'])->name('offices.update')->middleware(['role:tenant_admin', 'tenant.resource:office']);
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports')->middleware('role:tenant_admin');
    Route::get('/reports/download', [AdminController::class, 'downloadReport'])->name('reports.download')->middleware('role:tenant_admin');
    Route::get('/rbac', [AdminRbacController::class, 'edit'])->name('rbac.edit')->middleware('role:tenant_admin');
    Route::put('/rbac', [AdminRbacController::class, 'update'])->name('rbac.update')->middleware('role:tenant_admin');
    Route::get('/settings', [AdminAccountSettingsController::class, 'edit'])->name('settings.edit')->middleware('role:tenant_admin');
    Route::put('/settings', [AdminAccountSettingsController::class, 'update'])->name('settings.update')->middleware('role:tenant_admin');
    Route::get('/customization', [CustomizationController::class, 'index'])->name('customization.index')->middleware('role:tenant_admin');
    Route::put('/customization', [CustomizationController::class, 'update'])->name('customization.update')->middleware('role:tenant_admin');
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index')->middleware('role:tenant_admin');
    Route::get('/users/pending', [AdminController::class, 'pendingAccounts'])->name('users.pending')->middleware('role:tenant_admin');
    Route::get('/users/archived', [AdminController::class, 'archivedAccounts'])->name('users.archived')->middleware('role:tenant_admin');
    Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve')->middleware(['role:tenant_admin', 'tenant.resource:user']);
    Route::post('/users/{user}/archive', [AdminController::class, 'archiveUser'])->name('users.archive')->middleware(['role:tenant_admin', 'tenant.resource:user']);
    Route::post('/users/{user}/recover', [AdminController::class, 'recoverUser'])->name('users.recover')->middleware(['role:tenant_admin', 'tenant.resource:user']);
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy')->middleware(['role:tenant_admin', 'tenant.resource:user']);
});
