<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LocaleMiddleware;
use Modules\Inventory\Http\Controllers\UnitController;
use Modules\Inventory\Http\Controllers\InventoryItemController;
use Modules\Inventory\Http\Controllers\InventoryItemCategoryController;
use Modules\Inventory\Http\Controllers\InventoryStockController;
use Modules\Inventory\Http\Controllers\InventoryMovementController;
use Modules\Inventory\Http\Controllers\InventoryRecipeController;
use Modules\Inventory\Http\Controllers\InventorySettingController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\Http\Controllers\PurchaseReturnController;
use Modules\Inventory\Http\Controllers\ReportController;
use Modules\Inventory\Http\Controllers\InventoryDashboardController;
use Modules\Inventory\Http\Controllers\SupplierController;
use Modules\Inventory\Http\Controllers\PurchaseLocationController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', config('jetstream.auth_session'), 'verified', LocaleMiddleware::class])->prefix('inventory')->group(function () {
    Route::get('dashboard', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::resource('units', UnitController::class);
    Route::resource('inventory-item-categories', InventoryItemCategoryController::class);
    Route::resource('inventory-items', InventoryItemController::class);
    Route::resource('inventory-stocks', InventoryStockController::class);
    Route::get('inventory-movements/export', [InventoryMovementController::class, 'export'])->name('inventory-movements.export');
    Route::resource('inventory-movements', InventoryMovementController::class);
    Route::resource('recipes', InventoryRecipeController::class);
    Route::resource('purchases', PurchaseOrderController::class);
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('stock-transfers', \Modules\Inventory\Http\Controllers\StockTransferController::class);
    Route::resource('inventory-settings', InventorySettingController::class);
    Route::get('locations', [PurchaseLocationController::class, 'index'])->name('inventory.locations.index');
    
    Route::controller(PurchaseOrderController::class)->group(function () {
        Route::get('purchases/{purchase_order}/pdf', 'generatePdf')->name('purchases.pdf');
    });

    // Payment Accounts & Reports
    Route::resource('payment-accounts', \Modules\Inventory\Http\Controllers\PaymentAccountController::class)
        ->middleware('can:Show Payment Account');
    Route::prefix('payment-accounts')->name('payment-accounts.')->group(function () {
        Route::get('reports/account-report', [\Modules\Inventory\Http\Controllers\PaymentAccountController::class, 'report'])
            ->middleware('can:Show Payment Account Report')
            ->name('report');
        Route::get('reports/balance-sheet', [\Modules\Inventory\Http\Controllers\PaymentAccountController::class, 'balanceSheet'])
            ->middleware('can:Show Payment Account Balance Sheet')
            ->name('balance-sheet');
        Route::get('reports/trial-balance', [\Modules\Inventory\Http\Controllers\PaymentAccountController::class, 'trialBalance'])
            ->middleware('can:Show Payment Account Trial Balance')
            ->name('trial-balance');
        Route::get('reports/cash-flow', [\Modules\Inventory\Http\Controllers\PaymentAccountController::class, 'cashFlow'])
            ->middleware('can:Show Payment Account Cash Flow')
            ->name('cash-flow');
        // Export Route
        Route::get('reports/export', [\Modules\Inventory\Http\Controllers\PaymentAccountController::class, 'exportReport'])
            ->middleware('can:Show Payment Account Report')
            ->name('export');
    });

    // New Reports Section
    Route::prefix('reports')->name('inventory.reports.')->group(function () {
        Route::get('usage', [ReportController::class, 'usage'])->name('usage');
        Route::get('turnover', [ReportController::class, 'turnover'])->name('turnover');
        Route::get('forecasting', [ReportController::class, 'forecasting'])->name('forecasting');
        Route::get('cogs', [ReportController::class, 'cogs'])->name('cogs');
        Route::get('profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
    });
});
