<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\RedeemController;
use App\Http\Controllers\RedeemHistoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
| Modul-modul berikut (Master Item, Kategori, User, Stock Opname, Redeem,
| Laporan, dsb.) akan ditambahkan bertahap pada tahap berikutnya, masing-
| masing dengan Controller + Service + Repository sendiri, dibungkus
| middleware role & can_write agar Viewer hanya bisa membaca.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin,area_manager,admin,staff,viewer'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |----------------------------------------------------------------------
    | Master Item
    |----------------------------------------------------------------------
    | Read (index/data) terbuka untuk semua role login (termasuk Viewer).
    | Create/update/delete/import dibatasi lewat Policy ItemPolicy.
    */
    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/data', [ItemController::class, 'data'])->name('data');
        Route::get('/export/excel', [ItemController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/csv', [ItemController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/pdf', [ItemController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/import', [ItemController::class, 'showImportForm'])->name('import.form');
        Route::get('/import/template', [ItemController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [ItemController::class, 'import'])->name('import');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/', [ItemController::class, 'store'])->name('store');
        Route::get('/{item}/edit', [ItemController::class, 'edit'])->name('edit');
        Route::put('/{item}', [ItemController::class, 'update'])->name('update');
        Route::delete('/{item}', [ItemController::class, 'destroy'])->name('destroy');
        Route::post('/quick-store', [ItemController::class, 'quickStore'])->name('quick-store');
    });

    /*
    |----------------------------------------------------------------------
    | Master Kategori
    |----------------------------------------------------------------------
    | can_write middleware (di dalam controller) memblokir non-GET untuk Viewer.
    */
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/data', [CategoryController::class, 'data'])->name('data');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Master Outlet / Store
    |----------------------------------------------------------------------
    */
    Route::prefix('stores')->name('stores.')->group(function () {
        Route::get('/', [StoreController::class, 'index'])->name('index');
        Route::get('/data', [StoreController::class, 'data'])->name('data');
        Route::post('/', [StoreController::class, 'store'])->name('store');
        Route::put('/{store}', [StoreController::class, 'update'])->name('update');
        Route::delete('/{store}', [StoreController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Master Brand & Master Supplier (data pendukung Master Item)
    |----------------------------------------------------------------------
    */
    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::get('/data', [BrandController::class, 'data'])->name('data');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/data', [SupplierController::class, 'data'])->name('data');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Master User (hanya Super Admin & Admin — dibatasi via UserPolicy)
    |----------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/data', [UserController::class, 'data'])->name('data');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
    });

    /*
    |----------------------------------------------------------------------
    | Stock Opname
    |----------------------------------------------------------------------
    */
    Route::prefix('stock-opname')->name('stock-opname.')->group(function () {
        Route::get('/', [StockOpnameController::class, 'index'])->name('index');
        Route::get('/data', [StockOpnameController::class, 'data'])->name('data');
        Route::post('/start', [StockOpnameController::class, 'start'])->name('start');
        Route::get('/{opname}/scan', [StockOpnameController::class, 'scanPage'])->name('scan');
        Route::post('/{opname}/scan', [StockOpnameController::class, 'scan'])->name('scan.submit');
        Route::post('/{opname}/undo', [StockOpnameController::class, 'undo'])->name('undo');
        Route::post('/{opname}/reset', [StockOpnameController::class, 'reset'])->name('reset');
        Route::post('/{opname}/complete', [StockOpnameController::class, 'complete'])->name('complete');
        Route::get('/{opname}', [StockOpnameController::class, 'show'])->name('show');
        Route::get('/{opname}/berita-acara', [StockOpnameController::class, 'generateBeritaAcara'])->name('berita-acara');
        Route::get('/{opname}/export/excel', [StockOpnameController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{opname}/export/pdf', [StockOpnameController::class, 'exportPdf'])->name('export.pdf');
        Route::delete('/{opname}', [StockOpnameController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Redeem Hadiah & History Redeem
    |----------------------------------------------------------------------
    */
    Route::prefix('redeem')->name('redeem.')->group(function () {
        Route::get('/', [RedeemController::class, 'index'])->name('index');
        Route::get('/offline', [RedeemController::class, 'offline'])->name('offline');
        Route::post('/offline-sync', [RedeemController::class, 'syncOffline'])->name('offline-sync');
        Route::post('/scan-ticket', [RedeemController::class, 'scanTicket'])->name('scan-ticket');
        Route::delete('/ticket/{scanId}', [RedeemController::class, 'deleteTicket'])->name('delete-ticket');
        Route::post('/reset-tickets', [RedeemController::class, 'resetTickets'])->name('reset-tickets');
        Route::post('/manual-ticket', [RedeemController::class, 'addManualTicket'])->name('manual-ticket');
        Route::post('/member-balance', [RedeemController::class, 'setMemberBalance'])->name('member-balance');
        Route::post('/scan-item', [RedeemController::class, 'scanItem'])->name('scan-item');
        Route::post('/update-qty', [RedeemController::class, 'updateQty'])->name('update-qty');
        Route::post('/remove-item', [RedeemController::class, 'removeItem'])->name('remove-item');
        Route::post('/finish', [RedeemController::class, 'finish'])->name('finish');
        Route::get('/search-items', [RedeemController::class, 'searchItems'])->name('search-items');
        Route::get('/struk/{transaction}', [RedeemController::class, 'struk'])->name('struk');

        Route::get('/history', [RedeemHistoryController::class, 'index'])->name('history');
        Route::get('/history/data', [RedeemHistoryController::class, 'data'])->name('history.data');
        Route::get('/history/export/excel', [RedeemHistoryController::class, 'exportExcel'])->name('history.export.excel');
        Route::get('/history/export/pdf', [RedeemHistoryController::class, 'exportPdf'])->name('history.export.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Log Aktivitas
    |----------------------------------------------------------------------
    */
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/data', [ActivityLogController::class, 'data'])->name('data');
    });

    /*
    |----------------------------------------------------------------------
    | Laporan
    |----------------------------------------------------------------------
    */
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/data', [LaporanController::class, 'data'])->name('data');
        Route::get('/export/excel', [LaporanController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [LaporanController::class, 'exportPdf'])->name('export.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Notifikasi
    |----------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    /*
    |----------------------------------------------------------------------
    | Setting (Super Admin only)
    |----------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::get('/backup', [SettingController::class, 'backup'])->name('backup');
        Route::get('/backup/{filename}', [SettingController::class, 'downloadBackup'])->name('backup.download');
        Route::post('/restore', [SettingController::class, 'restore'])->name('restore');
    });
});
