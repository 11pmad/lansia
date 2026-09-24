<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\KunjunganFormController;
use App\Http\Controllers\LayananFormController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatistikController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public or guest landing
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Authenticated user routes (petugas & admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Laporan Bulanan
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/laporan', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/laporan/{report}/finalize', [ReportController::class, 'finalize'])->name('reports.finalize');
    Route::post('/laporan/{report}/reopen', [ReportController::class, 'reopen'])->name('reports.reopen');

    // Form Kunjungan & Form Layanan per Kelurahan
    Route::get('/laporan/{report}/kunjungan', [KunjunganFormController::class, 'edit'])->name('forms.kunjungan.edit');
    Route::put('/laporan/{report}/kunjungan/{kelurahan}', [KunjunganFormController::class, 'update'])->name('forms.kunjungan.update');

    Route::get('/laporan/{report}/layanan', [LayananFormController::class, 'edit'])->name('forms.layanan.edit');
    Route::put('/laporan/{report}/layanan/{kelurahan}', [LayananFormController::class, 'update'])->name('forms.layanan.update');

    // Rekapitulasi Laporan
    Route::get('/laporan/{report}/rekap', [RecapController::class, 'show'])->name('reports.recap');

    // Fitur OCR / Pindai Formulir Tulisan Tangan
    Route::get('/scan', [App\Http\Controllers\OcrController::class, 'create'])->name('scan.create');
    Route::post('/scan', [App\Http\Controllers\OcrController::class, 'store'])->name('scan.store');
    Route::get('/scan/{ocrUpload}', [App\Http\Controllers\OcrController::class, 'show'])->name('scan.show');
    Route::get('/scan/{ocrUpload}/image', [App\Http\Controllers\OcrController::class, 'image'])->name('scan.image');
    Route::get('/scan/{ocrUpload}/status', [App\Http\Controllers\OcrController::class, 'status'])->name('scan.status');
    Route::post('/scan/{ocrUpload}/apply', [App\Http\Controllers\OcrController::class, 'apply'])->name('scan.apply');

    // Statistik & Tren
    Route::get('/statistik', [StatistikController::class, 'index'])->name('stats.index');

    // Ekspor Excel (Format Resmi Dinkes TA 2026)
    Route::get('/ekspor', [ExportController::class, 'index'])->name('export.index');
    Route::get('/ekspor/download', [ExportController::class, 'download'])->name('export.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin-only master routes
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/kelurahan', function () {
        return Inertia::render('Master/Kelurahan');
    })->name('kelurahan.index');

    Route::get('/pengguna', function () {
        return Inertia::render('Master/Users');
    })->name('users.index');
});

require __DIR__.'/auth.php';
