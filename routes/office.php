<?php

use App\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Office Staff Routes (RBAC: role:office_staff, prefix: office)
|--------------------------------------------------------------------------
*/

Route::middleware('role:office_staff')->prefix('office')->name('office.')->group(function () {
    Route::get('/', [OfficeController::class, 'dashboard'])->name('dashboard');
    Route::get('/qr', [OfficeController::class, 'qr'])->name('qr');
    Route::get('/qr/image', [OfficeController::class, 'qrCodeImage'])->name('qr.image');
    Route::post('/call-next', [OfficeController::class, 'callNext'])->name('call-next');
    Route::patch('/queue/{queueEntry}', [OfficeController::class, 'updateQueueStatus'])->name('queue.update');
    Route::post('/appointments/{appointment}/accept', [OfficeController::class, 'acceptAppointment'])->name('appointments.accept');
    Route::post('/appointments/{appointment}/complete', [OfficeController::class, 'completeAppointment'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/cancel', [OfficeController::class, 'cancelAppointment'])->name('appointments.cancel');
    Route::get('/reports', [OfficeController::class, 'reports'])->name('reports');
    Route::get('/reports/download', [OfficeController::class, 'downloadReport'])->name('reports.download');
    Route::get('/activity', [OfficeController::class, 'activity'])->name('activity');
});
