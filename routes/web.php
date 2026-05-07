<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetCodeSettingController;
use App\Http\Controllers\AssetExportController;
use App\Http\Controllers\AssetLoanController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BarcodeScanController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Routes (Accessible without login)
Route::get('/scan/{code}', [\App\Http\Controllers\PublicAssetController::class, 'show'])
    ->name('public.asset.show')
    ->where('code', '.*');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Assets CRUD & Trash
    Route::get('/assets/trash', [AssetController::class, 'trash'])->name('assets.trash');
    Route::post('/assets/{id}/restore', [AssetController::class, 'restore'])->name('assets.restore');
    Route::delete('/assets/{id}/force-delete', [AssetController::class, 'forceDelete'])->name('assets.force-delete');
    Route::post('/assets/bulk-restore', [AssetController::class, 'bulkRestore'])->name('assets.bulk-restore');
    Route::post('/assets/bulk-force-delete', [AssetController::class, 'bulkForceDelete'])->name('assets.bulk-force-delete');
    Route::resource('assets', AssetController::class)->except(['create', 'edit']);
    Route::patch('/assets/{asset}/available', [AssetController::class, 'markAsAvailable'])->name('assets.available');
    Route::get('/assets/monitoring/expirations', [AssetController::class, 'expirations'])->name('assets.expirations');
    Route::post('/assets/bulk-delete', [AssetController::class, 'bulkDelete'])->name('assets.bulk-delete');
    Route::patch('/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.status');
    Route::patch('/assets/{asset}/return-to-user', [AssetController::class, 'returnToUser'])->name('assets.return-to-user');
    Route::get('/assets/{asset}/qr-standard', [AssetController::class, 'qrStandard'])->name('assets.qr.standard');
    Route::get('/assets/{asset}/qr-stylized', [AssetController::class, 'qrStylized'])->name('assets.qr.stylized');

    // Asset Printing
    Route::get('/assets/print/select', [\App\Http\Controllers\AssetPrintController::class, 'index'])->name('assets.print-index');
    Route::get('/assets/print/layout', [\App\Http\Controllers\AssetPrintController::class, 'print'])->name('assets.print');

    // Asset Export & Import
    Route::get('/assets/export/excel', [\App\Http\Controllers\AssetExportController::class, 'excel'])->name('assets.export.excel');
    Route::get('/assets/export/pdf', [\App\Http\Controllers\AssetExportController::class, 'pdf'])->name('assets.export.pdf');
    Route::post('/assets/import', [\App\Http\Controllers\AssetExportController::class, 'import'])->name('assets.import');
    Route::get('/assets/import/template', [\App\Http\Controllers\AssetExportController::class, 'template'])->name('assets.import.template');

    // Categories & Subcategories
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::post('/categories/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('categories.bulk-delete');
    Route::post('/subcategories', [CategoryController::class, 'storeSubcategory'])->name('subcategories.store');
    Route::put('/subcategories/{subcategory}', [CategoryController::class, 'updateSubcategory'])->name('subcategories.update');
    Route::delete('/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');
    Route::post('/subcategories/bulk-delete', [CategoryController::class, 'bulkDeleteSub'])->name('subcategories.bulk-delete-sub');

    // Locations CRUD
    Route::resource('locations', LocationController::class)->except(['create', 'edit', 'show']);
    Route::post('/locations/bulk-delete', [LocationController::class, 'bulkDelete'])->name('locations.bulk-delete');

    // Departments CRUD
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except(['create', 'edit', 'show']);
    Route::post('/departments/bulk-delete', [\App\Http\Controllers\DepartmentController::class, 'bulkDelete'])->name('departments.bulk-delete');

    // Vendors CRUD
    Route::resource('vendors', \App\Http\Controllers\VendorController::class)->except(['create', 'edit', 'show']);
    Route::post('/vendors/bulk-delete', [\App\Http\Controllers\VendorController::class, 'bulkDelete'])->name('vendors.bulk-delete');

    // Asset Movements
    Route::post('/movements', [AssetMovementController::class, 'store'])->name('movements.store');

    // Maintenance Logs
    Route::resource('maintenance', MaintenanceLogController::class)->except(['create', 'edit', 'show']);
    Route::post('/maintenance/bulk-delete', [MaintenanceLogController::class, 'bulkDelete'])->name('maintenance.bulk-delete');
    Route::patch('/maintenance/{maintenance}/complete', [MaintenanceLogController::class, 'complete'])->name('maintenance.complete');

    // Barcode Scanner
    Route::get('/scanner', [BarcodeScanController::class, 'index'])->name('scanner.index');
    Route::post('/scanner/lookup', [BarcodeScanController::class, 'lookup'])->name('scanner.lookup');

    // Settings: Asset Code Template
    Route::get('/settings/asset-code', [AssetCodeSettingController::class, 'index'])->name('settings.asset-code');
    Route::put('/settings/asset-code', [AssetCodeSettingController::class, 'update'])->name('settings.asset-code.update');
    Route::post('/settings/asset-code/reset', [AssetCodeSettingController::class, 'resetCounter'])->name('settings.asset-code.reset');
    Route::post('/settings/asset-code/preview', [AssetCodeSettingController::class, 'preview'])->name('settings.asset-code.preview');

    // User Management (Admin Only)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'edit', 'show']);
        Route::post('/users/bulk-delete', [\App\Http\Controllers\UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    });

    // Audits
    Route::resource('audits', \App\Http\Controllers\AuditController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('/audits/{audit}/scan', [\App\Http\Controllers\AuditController::class, 'scan'])->name('audits.scan');
    Route::post('/audits/{audit}/complete', [\App\Http\Controllers\AuditController::class, 'complete'])->name('audits.complete');
    Route::get('/audits/{audit}/export-pdf', [\App\Http\Controllers\AuditController::class, 'exportPdf'])->name('audits.export-pdf');
    Route::post('/audits/bulk-delete', [AuditController::class, 'bulkDelete'])->name('audits.bulk-delete');
    
    // Asset Loans
    Route::get('/loans', [AssetLoanController::class, 'index'])->name('loans.index');
    Route::post('/loans', [AssetLoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/bulk-delete', [AssetLoanController::class, 'bulkDelete'])->name('loans.bulk-delete');
    Route::patch('/loans/{loan}/return', [AssetLoanController::class, 'return'])->name('loans.return');

    // API: Check & Suggest Asset Code
    Route::get('/assets/check-code', [AssetController::class, 'checkCode'])->name('assets.check-code');
    Route::post('/assets/generate-code', \App\Http\Controllers\AssetCodePreviewController::class)->name('assets.generate-code');
});

require __DIR__.'/auth.php';
