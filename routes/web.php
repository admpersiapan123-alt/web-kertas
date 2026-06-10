<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockKertasController;
use App\Http\Controllers\ShiftRollController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\ImportStockController;


// 1. DASHBOARD UTAMA
Route::get('/', [DashboardController::class, 'index']); 

// ==========================================
// 2. MENU 1: SCAN SHIFT ROLL FORKLIFT
// ==========================================
Route::prefix('shift')->group(function () {
    Route::get('/', [ShiftRollController::class, 'history']); 
    Route::post('/store', [ShiftRollController::class, 'store']);
    Route::get('/{id}/edit', [ShiftRollController::class, 'edit']);
    Route::post('/{id}/update', [ShiftRollController::class, 'update']);
    
    // Dashboard Supir & Aksi
    Route::get('/{id}/dashboard', [ShiftRollController::class, 'dashboard']);
    Route::post('/kembali-roll/{id}', [ShiftRollController::class, 'postKembaliRoll']);
    Route::post('/batal-roll/{id}', [ShiftRollController::class, 'batalRoll']);
    Route::get('/{id}/print', [ShiftRollController::class, 'printReport']);
});


// ==========================================
// 3. MENU 2: HITUNG KEBUTUHAN CORR PER SPK
// ==========================================
Route::prefix('hitung-spk')->group(function () {
    Route::get('/', [SpkController::class, 'index']); 
    
    // Menu Riwayat Khusus
    Route::get('/riwayat', [SpkController::class, 'riwayat']); 
    
    // Rute Manual & CRUD
    Route::get('/manual', [SpkController::class, 'kalkulasiManual']); 
    Route::post('/manual/store', [SpkController::class, 'storeManual']);
    Route::get('/edit/{id}', [SpkController::class, 'edit']); 
    Route::post('/update/{id}', [SpkController::class, 'update']);
    Route::post('/delete/{id}', [SpkController::class, 'destroy']); 
    Route::get('/menu2', [SpkController::class, 'menu2']);

    // Tambahkan ini di dalam Route::prefix('hitung-spk')->group(function () { ... })
    Route::get('/otomatis', [SpkController::class, 'kalkulasiOtomatis']);
    Route::post('/otomatis/store', [SpkController::class, 'storeOtomatis']);

    Route::get('/sapujagat', [SpkController::class, 'sapuJagat']);
    Route::post('/sapujagat/store', [SpkController::class, 'storeSapuJagat']);

    // Taruh di dalam kelompok Route hitung-spk Anda
    Route::post('/sapujagat/re-run/{id}', [SpkController::class, 'reRunSapuJagat']);

    Route::post('sapujagat/scan-ai', [\App\Http\Controllers\SpkController::class, 'scanFotoAi']);
});

// 4. DATA MASTER & SCAN UMUM
Route::get('/search', [StockKertasController::class, 'index']); 
Route::get('/scan', [StockKertasController::class, 'scanView']); 

// 5. API ROUTES
Route::get('/api/check-roll/{no_roll}', [StockKertasController::class, 'checkRollApi']);
Route::post('/api/shift/{id}/ambil-roll', [ShiftRollController::class, 'postAmbilRoll']);

// Route untuk menampilkan halaman form upload CSV
Route::get('/stock-kertas/import', [ImportStockController::class, 'showImportForm']);

// Route untuk memproses file CSV yang diupload
Route::post('/stock-kertas/import', [ImportStockController::class, 'importStockCSV']);