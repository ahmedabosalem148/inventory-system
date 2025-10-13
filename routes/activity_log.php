<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;

/*
|--------------------------------------------------------------------------
| Activity Log Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('activity-log')->name('activity-log.')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    Route::get('/{activity}', [ActivityLogController::class, 'show'])->name('show');
});
