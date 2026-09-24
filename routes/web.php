<?php

use App\Http\Controllers\KunjunganFormController;
use App\Http\Controllers\LayananFormController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\ReportController;
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
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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

    // Placeholder Scan & Statistik
    Route::get('/scan', function () {
        return Inertia::render('Scan/Create');
    })->name('scan.create');

    Route::get('/statistik', function () {
        return Inertia::render('Statistik');
    })->name('stats.index');

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
