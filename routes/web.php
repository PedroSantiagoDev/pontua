<?php

use App\Http\Controllers\AttendanceExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/pontua'));

Route::middleware(['auth'])->group(function (): void {
    Route::get('/attendance/export/pdf/{employee}/{period}', [AttendanceExportController::class, 'pdf'])
        ->name('attendance.export.pdf');
    Route::get('/attendance/export/excel/{employee}/{period}', [AttendanceExportController::class, 'excel'])
        ->name('attendance.export.excel');
});
