<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/export/remote-crm', [ExportController::class, 'exportRemoteCrm'])->name('export.remote-crm');
});
