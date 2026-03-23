<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:student')->prefix('tenant/app')->name('student.')->group(function () {
    Route::get('/', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/offices', [StudentController::class, 'offices'])->name('offices');
    Route::post('/offices/{office}/queue', [StudentController::class, 'getQueueNumber'])->name('get-queue')->middleware('tenant.resource:office');
    Route::get('/offices/{office}/book', [StudentController::class, 'showBookAppointment'])->name('book')->middleware('tenant.resource:office');
    Route::post('/offices/{office}/book', [StudentController::class, 'bookAppointment'])->name('book.store')->middleware('tenant.resource:office');
    Route::get('/offices/{office}/live-queue', [StudentController::class, 'liveQueue'])->name('live-queue')->middleware('tenant.resource:office');
    Route::get('/queue/{referenceCode}', [StudentController::class, 'queueTracker'])->name('queue-tracker');
});
