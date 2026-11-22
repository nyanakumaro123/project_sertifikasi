<?php

use App\Http\Controllers\GarasiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SewaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Halaman awal: arahkan sesuai status login dan role
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return redirect()->route(Auth::user()->role === 'admin' ? 'garasi.index' : 'sewa.index');
});

// ---------- Tamu (belum login) ----------
Route::middleware('guest')->group(function () {
    Route::view('/register', 'autentikasi.register')->name('register');
    Route::post('/register', [UserController::class, 'register'])->name('register.store');

    Route::view('/login', 'autentikasi.login')->name('login');
    Route::post('/login', [UserController::class, 'login'])->name('login.store');
});

// ---------- Semua pengguna yang sudah login ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');

    // Admin dan Penyewa sama-sama membuka /sewa, tetapi view-nya berbeda sesuai role
    Route::get('/sewa', [SewaController::class, 'index'])->name('sewa.index');
});

// ---------- Khusus Admin ----------
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/verifikasi-ktp', [UserController::class, 'verifikasiKTP'])
        ->name('verifikasi-ktp.index');
    Route::post('/verifikasi-ktp/{userId}/{status}', [UserController::class, 'verifikasiKTP'])
        ->whereNumber('userId')
        ->where('status', 'approved|rejected')
        ->name('verifikasi-ktp.update');

    Route::get('/garasi', [GarasiController::class, 'index'])->name('garasi.index');
    Route::view('/garasi/buat', 'garasi.create-batch')->name('garasi.create');
    Route::post('/garasi', [GarasiController::class, 'storeBatch'])->name('garasi.store-batch');

    Route::get('/laporan', [LaporanController::class, 'generateLaporan'])->name('laporan.index');

    Route::get('/sewa/{rentalId}/perpanjang', [SewaController::class, 'extend'])
        ->whereNumber('rentalId')
        ->name('sewa.extend');
    Route::post('/sewa/{rentalId}/perpanjang', [SewaController::class, 'extend'])
        ->whereNumber('rentalId')
        ->name('sewa.extend.store');
});

// ---------- Khusus Penyewa ----------
Route::middleware(['auth', 'role:penyewa'])->group(function () {
    Route::post('/sewa', [SewaController::class, 'store'])->name('sewa.store');
});
