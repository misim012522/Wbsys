<?php

use App\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Office Staff Routes (RBAC: role:office_staff, prefix: office)
|--------------------------------------------------------------------------
*/

Route::prefix('office')->name('office.')->group(function () {
    Route::get('/', [OfficeController::class, 'dashboard'])->name('dashboard')->middleware(['role:office_staff', 'permission:office.dashboard']);
    Route::get('/qr', [OfficeController::class, 'qr'])->name('qr')->middleware(['role:office_staff', 'permission:office.qr']);
    Route::get('/qr/image', [OfficeController::class, 'qrCodeImage'])->name('qr.image')->middleware(['role:office_staff', 'permission:office.qr']);
    Route::post('/call-next', [OfficeController::class, 'callNext'])->name('call-next')->middleware(['role:office_staff', 'permission:office.queue.manage']);
    Route::patch('/queue/{queueEntry}', [OfficeController::class, 'updateQueueStatus'])->name('queue.update')->middleware(['role:office_staff', 'permission:office.queue.manage']);
    Route::post('/queue/clear-all', [OfficeController::class, 'clearAllQueues'])->name('queue.clear-all')->middleware(['role:office_staff', 'permission:office.queue.manage']);
    Route::get('/reports', [OfficeController::class, 'reports'])->name('reports')->middleware(['role:office_staff', 'permission:reports.view']);
    Route::get('/reports/download', [OfficeController::class, 'downloadReport'])->name('reports.download')->middleware(['role:office_staff', 'permission:reports.view']);
    Route::get('/notifications', [OfficeController::class, 'notifications'])->name('notifications')->middleware(['role:office_staff', 'permission:office.dashboard']);
});
