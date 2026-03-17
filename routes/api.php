<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth', 'tenant.context'])->prefix('api')->group(function () {
    // Real-time data endpoints for dashboard refresh
    Route::get('/offices/{office}/queue-count', [ApiController::class, 'queueCount'])->name('api.offices.queue-count');
    Route::get('/offices/{office}/appointments-today', [ApiController::class, 'appointmentsToday'])->name('api.offices.appointments-today');
    Route::get('/offices/{office}/completed-today', [ApiController::class, 'completedToday'])->name('api.offices.completed-today');
    Route::get('/offices/{office}/queue-list', [ApiController::class, 'queueList'])->name('api.offices.queue-list');
    Route::get('/offices/{office}/appointments-list', [ApiController::class, 'appointmentsList'])->name('api.offices.appointments-list');
});
