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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard')->middleware('permission:users.manage,offices.manage,queue.manage,appointments.manage,reports.view,office.serve');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile')->middleware('permission:users.manage,offices.manage,queue.manage,appointments.manage,reports.view,office.serve');
    Route::get('/qr', [AdminController::class, 'qrCodes'])->name('qr')->middleware('permission:office.serve');
    Route::get('/qr/{office}/image', [AdminController::class, 'qrCodeImage'])->name('qr.image')->middleware(['permission:office.serve', 'tenant.resource:office']);
    Route::get('/serve/{office}', [AdminController::class, 'serveOffice'])->name('serve')->middleware(['permission:office.serve', 'tenant.resource:office']);
    Route::post('/serve/{office}/call-next', [AdminController::class, 'callNext'])->name('call-next')->middleware(['permission:queue.manage', 'tenant.resource:office']);
    Route::patch('/queue/{queueEntry}', [AdminController::class, 'updateQueueStatus'])->name('queue.update')->middleware(['permission:queue.manage', 'tenant.resource:queueEntry']);
    Route::post('/appointments/{appointment}/accept', [AdminController::class, 'acceptAppointment'])->name('appointments.accept')->middleware(['permission:appointments.manage', 'tenant.resource:appointment']);
    Route::post('/appointments/{appointment}/complete', [AdminController::class, 'completeAppointment'])->name('appointments.complete')->middleware(['permission:appointments.manage', 'tenant.resource:appointment']);
    Route::post('/appointments/{appointment}/cancel', [AdminController::class, 'cancelAppointment'])->name('appointments.cancel')->middleware(['permission:appointments.manage', 'tenant.resource:appointment']);
    Route::get('/offices', [AdminController::class, 'offices'])->name('offices')->middleware('permission:offices.manage');
    Route::get('/offices/create', [AdminController::class, 'createOffice'])->name('offices.create')->middleware('permission:offices.manage');
    Route::post('/offices', [AdminController::class, 'storeOffice'])->name('offices.store')->middleware('permission:offices.manage');
    Route::get('/offices/{office}/edit', [AdminController::class, 'editOffice'])->name('offices.edit')->middleware(['permission:offices.manage', 'tenant.resource:office']);
    Route::put('/offices/{office}', [AdminController::class, 'updateOffice'])->name('offices.update')->middleware(['permission:offices.manage', 'tenant.resource:office']);
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports')->middleware('permission:reports.view');
    Route::get('/reports/download', [AdminController::class, 'downloadReport'])->name('reports.download')->middleware('permission:reports.view');
    Route::get('/settings', [AdminAccountSettingsController::class, 'edit'])->name('settings.edit')->middleware('role:tenant_admin');
    Route::put('/settings', [AdminAccountSettingsController::class, 'update'])->name('settings.update')->middleware('role:tenant_admin');
    Route::get('/customization', [CustomizationController::class, 'index'])->name('customization.index')->middleware('role:tenant_admin');
    Route::put('/customization', [CustomizationController::class, 'update'])->name('customization.update')->middleware('role:tenant_admin');
    Route::get('/roles', [AdminController::class, 'rolesIndex'])->name('roles.index')->middleware('role:tenant_admin');
    Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store')->middleware('role:tenant_admin');
    Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('roles.update')->middleware('role:tenant_admin');
    Route::patch('/roles/{role}/status', [AdminController::class, 'toggleRoleStatus'])->name('roles.status')->middleware('role:tenant_admin');
    Route::delete('/roles/{role}', [AdminController::class, 'destroyRole'])->name('roles.destroy')->middleware('role:tenant_admin');
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index')->middleware('permission:users.manage');
    Route::get('/users/pending', [AdminController::class, 'pendingAccounts'])->name('users.pending')->middleware('permission:users.manage');
    Route::get('/users/archived', [AdminController::class, 'archivedAccounts'])->name('users.archived')->middleware('permission:users.manage');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role')->middleware(['permission:users.manage', 'tenant.resource:user']);
    Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve')->middleware(['permission:users.manage', 'tenant.resource:user']);
    Route::post('/users/{user}/archive', [AdminController::class, 'archiveUser'])->name('users.archive')->middleware(['permission:users.manage', 'tenant.resource:user']);
    Route::post('/users/{user}/recover', [AdminController::class, 'recoverUser'])->name('users.recover')->middleware(['permission:users.manage', 'tenant.resource:user']);
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy')->middleware(['permission:users.manage', 'tenant.resource:user']);
});
