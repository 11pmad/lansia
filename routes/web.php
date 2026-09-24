<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public or guest landing
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Authenticated user routes (both petugas and admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/laporan', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

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
