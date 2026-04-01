<?php

use App\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Office Staff Routes (RBAC: role:office_staff, prefix: office)
|--------------------------------------------------------------------------
*/

Route::prefix('office')->name('office.')->group(function () {
    Route::get('/', [OfficeController::class, 'dashboard'])->name('dashboard')->middleware('permission:office.serve');
    Route::get('/qr', [OfficeController::class, 'qr'])->name('qr')->middleware('permission:office.serve');
    Route::get('/qr/image', [OfficeController::class, 'qrCodeImage'])->name('qr.image')->middleware('permission:office.serve');
    Route::post('/call-next', [OfficeController::class, 'callNext'])->name('call-next')->middleware('permission:office.serve');
    Route::patch('/queue/{queueEntry}', [OfficeController::class, 'updateQueueStatus'])->name('queue.update')->middleware('permission:office.serve');
    Route::post('/appointments/{appointment}/accept', [OfficeController::class, 'acceptAppointment'])->name('appointments.accept')->middleware('permission:office.serve');
    Route::post('/appointments/{appointment}/complete', [OfficeController::class, 'completeAppointment'])->name('appointments.complete')->middleware('permission:office.serve');
    Route::post('/appointments/{appointment}/cancel', [OfficeController::class, 'cancelAppointment'])->name('appointments.cancel')->middleware('permission:office.serve');
    Route::get('/reports', [OfficeController::class, 'reports'])->name('reports')->middleware('permission:reports.view');
    Route::get('/reports/download', [OfficeController::class, 'downloadReport'])->name('reports.download')->middleware('permission:reports.view');
    Route::get('/activity', [OfficeController::class, 'activity'])->name('activity')->middleware('permission:office.serve');
});
