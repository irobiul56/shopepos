<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DueCollectionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/shop/{id}/edit', [ShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop/{id}', [ShopController::class, 'update'])->name('shop.update');
    Route::post('/shop/{id}', [ShopController::class, 'update'])->name('shop.update');

    Route::resource('categories', CategoryController::class);
    Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
        ->name('categories.toggle-status');

    Route::resource('brands', BrandController::class);
    Route::put('brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->name('brands.toggle-status');
    
    Route::resource('units', UnitController::class);
    Route::put('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
    
    Route::resource('products', ProductController::class);
    Route::put('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

    Route::resource('customers', CustomerController::class);
    Route::put('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    Route::resource('suppliers', SupplierController::class);
    Route::put('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

    // Purchase routes
    Route::get('purchases/report-data', [PurchaseController::class, 'reportData'])->name('purchases.report-data');
    Route::get('purchases/{purchase}/print-data', [PurchaseController::class, 'printData'])->name('purchases.print-data');
    Route::put('purchases/{purchase}/toggle-status', [PurchaseController::class, 'toggleStatus'])->name('purchases.toggle-status');
    
    // Resource route should come AFTER custom routes
    Route::resource('purchases', PurchaseController::class);

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/store', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/invoice/{id}', [PosController::class, 'showInvoice'])->name('pos.invoice');

    Route::get('/reports', [ReportController::class, 'unified'])->name('report.unified');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('report.export');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/due-collection', [DueCollectionController::class, 'index'])->name('due-collection.index');
    Route::post('/due-collection/pay', [DueCollectionController::class, 'collectPayment'])->name('due-collection.pay');

    
    });

require __DIR__.'/auth.php';
