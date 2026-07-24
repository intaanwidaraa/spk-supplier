<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/laporan/export-excel', [App\Http\Controllers\LaporanController::class, 'exportExcel'])->name('laporan.export');
    Route::get('/laporan/cetak', [App\Http\Controllers\LaporanController::class, 'cetakPDF'])->name('laporan.cetak');
});
