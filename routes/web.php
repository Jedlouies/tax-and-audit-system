<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('auth.login');
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/reports/vat-pdf', [ReportController::class, 'exportVatPdf'])->name('reports.vat_pdf');
Route::get('/reports/slsp-excel', [ReportController::class, 'exportSlspExcel'])->name('reports.slsp_excel');
Route::get('/reports/qap-pdf', [ReportController::class, 'exportQapPdf'])->name('reports.qap_pdf');