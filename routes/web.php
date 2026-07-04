<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockKertasController;
use App\Http\Controllers\ShiftRollController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\ImportStockController;
use App\Http\Controllers\Versi2Controller;

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
    Route::post('/ubah-posisi/{id}', [ShiftRollController::class, 'ubahPosisiMesin'])->name('shift.ubahPosisi');
    Route::post('/transaksi/update-sisa', [ShiftRollController::class, 'updateSisaKilo']);
// Catatan: Sesuaikan 'ShiftController' dengan nama controller Anda.
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

// ==========================================
// 6. MENU VERSI 2 (FULL OTOMATIS & GLOBAL POOLING)
// ==========================================
Route::prefix('versi2')->group(function () {
    // A. Manajemen Shift & Scan Forklift V2 (Tanpa Posisi)
    Route::get('/scan-shift', [Versi2Controller::class, 'shiftIndex']);
    Route::post('/scan-shift/store', [Versi2Controller::class, 'shiftStore']);
    Route::get('/scan-shift/{id}/dashboard', [Versi2Controller::class, 'shiftDashboard']);
    Route::post('/api/shift/{id}/ambil-roll', [Versi2Controller::class, 'postAmbilRoll']);
    Route::post('/shift/kembali-roll/{id}', [Versi2Controller::class, 'postKembaliRoll']);
    Route::post('/shift/batal-roll/{id}', [Versi2Controller::class, 'batalRoll']);

    // B. Pencocokan AI Mak Comblang V2
    Route::get('/pencocokan', [Versi2Controller::class, 'pencocokanIndex']);
    Route::post('/pencocokan/store', [Versi2Controller::class, 'storePencocokan']);
    Route::post('/pencocokan/scan-ai', [Versi2Controller::class, 'scanFotoAiV2']); // Pakai AI Groq
    
    // C. Riwayat & Edit V2
    Route::get('/riwayat', [Versi2Controller::class, 'riwayatIndex']);
    Route::post('/pencocokan/re-run/{id}', [Versi2Controller::class, 'reRunPencocokan']);
    Route::post('/delete/{id}', [Versi2Controller::class, 'destroy']); 
    Route::get('/riwayat/{id}', [Versi2Controller::class, 'showDetail']);
    Route::get('/riwayat/{id}/edit', [Versi2Controller::class, 'editIndex']);
});

Route::get('/checker', [App\Http\Controllers\CheckerController::class, 'index']);
Route::post('/checker/store', [App\Http\Controllers\CheckerController::class, 'storePlan']);
Route::post('/checker/scan/save', [App\Http\Controllers\CheckerController::class, 'saveScan']);
Route::post('/checker/scan/delete', [App\Http\Controllers\CheckerController::class, 'deleteScan']);
Route::post('/checker/scan/fetch-kg', [App\Http\Controllers\CheckerController::class, 'fetchKg']);
Route::post('/checker/task/delete/{id}', [App\Http\Controllers\CheckerController::class, 'hapusTugas']);
Route::post('/checker/task/reset', [App\Http\Controllers\CheckerController::class, 'resetSemua']);
Route::post('/checker/task/submit/{id}', [App\Http\Controllers\CheckerController::class, 'submitTask']);
Route::get('/checker/riwayat', [App\Http\Controllers\CheckerController::class, 'riwayat']);
Route::post('/checker/task/revert/{id}', [App\Http\Controllers\CheckerController::class, 'batalSubmit']);
Route::post('/checker/task/push/{id}', [App\Http\Controllers\CheckerController::class, 'pushToMakComblang']);
Route::post('/checker/task/unmerge/{id}', [App\Http\Controllers\CheckerController::class, 'batalMerge']);

// Rute Halaman Utama V3
Route::get('/shift-v3', [App\Http\Controllers\ShiftV3Controller::class, 'index']);

// Rute yang kemarin sudah ada:
Route::get('/shift-v3/print/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'printReport']);
Route::post('/shift-v3/transaksi/update-sisa', [App\Http\Controllers\ShiftV3Controller::class, 'updateSisaKilo']);
Route::post('/shift-v3', [App\Http\Controllers\ShiftV3Controller::class, 'store']);
// Rute Scan & Daftar V3
Route::get('/shift-v3/scan/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'scan']);

// Rute Aksi AJAX dan Form V3
Route::post('/shift-v3/scan-ajax/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'storeScanAjax']);
Route::post('/shift-v3/kembali-roll/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'kembalikanRoll']);
Route::post('/shift-v3/batal-roll/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'batalRoll']);
Route::post('/shift-v3/ubah-posisi/{id}', [App\Http\Controllers\ShiftV3Controller::class, 'ubahPosisi']);

