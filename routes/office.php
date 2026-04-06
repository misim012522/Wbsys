<?php

use App\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Office Staff Routes (RBAC: role:office_staff, prefix: office)
|--------------------------------------------------------------------------
*/

Route::prefix('office')->name('office.')->group(function () {
    Route::get('/', [OfficeController::class, 'dashboard'])->name('dashboard')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::get('/qr', [OfficeController::class, 'qr'])->name('qr')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::get('/qr/image', [OfficeController::class, 'qrCodeImage'])->name('qr.image')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::post('/call-next', [OfficeController::class, 'callNext'])->name('call-next')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::patch('/queue/{queueEntry}', [OfficeController::class, 'updateQueueStatus'])->name('queue.update')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::post('/appointments/{appointment}/accept', [OfficeController::class, 'acceptAppointment'])->name('appointments.accept')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::post('/appointments/{appointment}/complete', [OfficeController::class, 'completeAppointment'])->name('appointments.complete')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::post('/appointments/{appointment}/cancel', [OfficeController::class, 'cancelAppointment'])->name('appointments.cancel')->middleware(['role:office_staff', 'permission:office.serve']);
    Route::get('/reports', [OfficeController::class, 'reports'])->name('reports')->middleware(['role:office_staff', 'permission:reports.view']);
    Route::get('/reports/download', [OfficeController::class, 'downloadReport'])->name('reports.download')->middleware(['role:office_staff', 'permission:reports.view']);
    Route::get('/activity', [OfficeController::class, 'activity'])->name('activity')->middleware(['role:office_staff', 'permission:office.serve']);
});
