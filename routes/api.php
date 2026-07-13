<?php

use App\Http\Controllers\CashBoxController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDebtController;
use App\Http\Controllers\CustomerReturnInvoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMedicalInfoController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockBatchController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierDebtController;
use App\Http\Controllers\SupplierReturnInvoiceController;
use App\Http\Controllers\UnitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Models\Governorate;
use App\Http\Resources\GovernorateResource;
use App\Http\Resources\CityResource;
use App\Models\City;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:3,1');

Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::post('/logout-all', [AuthController::class, 'logoutAll'])
    ->middleware('auth:sanctum');
Route::get('/governorates', function () {
    $governorates = Governorate::all();
    return GovernorateResource::collection($governorates);
});
Route::get('/cities', function () {
    $cities = City::all();
    return CityResource::collection($cities);
});
Route::get('/governorates-cities', function () {
    $governorates = Governorate::with('cities')->get();
    return GovernorateResource::collection($governorates);
});

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['throttle:1,1'])
    ->name('verification.send');

// Password reset routes
Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:1,1')
    ->name('password.forgot');

Route::get('/password/reset', [AuthController::class, 'redirectToApp'])
    ->middleware('signed')
    ->name('password.reset');

Route::post('/password/reset', [AuthController::class, 'resetPassword']);
//sign in with google
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::post('auth/google/complete-profile', [AuthController::class, 'completeProfile'])->middleware('auth:sanctum');


Route::prefix('pharmacy')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('/products', [InventoryController::class, 'getAllPharmacyProducts']);
        Route::get('/{productId}/stock-batches', [InventoryController::class, 'getProductStockBatches']);
        Route::get('/category/{categoryId}/products', [InventoryController::class, 'getProductsByCategory']);
    });

Route::prefix('products')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('{product}/batches/available', [ProductController::class, 'availableBatches']);
        Route::get('low-stock', [ProductController::class, 'lowStock']);
        Route::get('barcode/{barcode}',         [ProductController::class, 'lookupByBarcode']);
        Route::get('',                           [ProductController::class, 'index']);
        Route::post('',                          [ProductController::class, 'store']);
        Route::get('{product}',                 [ProductController::class, 'show']);
        Route::put('{product}',                 [ProductController::class, 'update']);
        Route::delete('{product}',              [ProductController::class, 'destroy']);
        Route::patch('{product}/restore',       [ProductController::class, 'restore'])->withTrashed();

        Route::get('/{product}/medical-info',    [ProductMedicalInfoController::class, 'show']);
        Route::put('{product}/medical-info',    [ProductMedicalInfoController::class, 'upsert']);
        Route::delete('{product}/medical-info', [ProductMedicalInfoController::class, 'destroy']);
    });

Route::prefix('suppliers')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('',                          [SupplierController::class, 'index']);
        Route::post('',                         [SupplierController::class, 'store']);
        Route::get('{supplier}',               [SupplierController::class, 'show']);
        Route::put('{supplier}',               [SupplierController::class, 'update']);
        Route::delete('{supplier}',        [SupplierController::class, 'destroy']);
        Route::patch('{supplier}/restore', [SupplierController::class, 'restore'])->withTrashed();
    });

Route::get('categories', [CategoryController::class, 'index']);
Route::get('units', [UnitController::class, 'index']);
Route::get('companies',  [CompanyController::class, 'index']);
Route::get('companies/{company}', [CompanyController::class, 'show']);

Route::middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('categories/{category}', [CategoryController::class, 'show']);

        Route::get('supplier-debts',                 [SupplierDebtController::class, 'index']);
        Route::get('supplier-debts/{supplierDebt}',  [SupplierDebtController::class, 'show']);
        Route::post('supplier-debts/{supplierDebt}/pay',[SupplierDebtController::class, 'pay']);

        Route::get('customer-debts',[CustomerDebtController::class, 'index']);
        Route::get('customer-debts/{customerDebt}',[CustomerDebtController::class, 'show']);
        Route::post('customer-debts/{customerDebt}/pay',[CustomerDebtController::class, 'pay']);


        Route::get('purchase-invoices',                    [PurchaseInvoiceController::class, 'index']);
        Route::post('purchase-invoices',                   [PurchaseInvoiceController::class, 'store']);
        Route::get('purchase-invoices/{purchaseInvoice}',  [PurchaseInvoiceController::class, 'show']);
        Route::put('purchase-invoices/{purchaseInvoice}',  [PurchaseInvoiceController::class, 'update']);
        Route::patch('purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel']);

        Route::get('stock-batches',[StockBatchController::class, 'index']);
        Route::get('stock-batches/{stockBatch}', [StockBatchController::class, 'show']);
        Route::patch('stock-batches/{stockBatch}/mark-expired', [StockBatchController::class, 'markExpired']);

        Route::get('stock-movements', [StockMovementController::class, 'index']);
        Route::get('stock-movements/{stockMovement}',[StockMovementController::class, 'show']);

        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);
        Route::post('stock-adjustments',  [StockAdjustmentController::class, 'store']);
        Route::post('stock-adjustments/bulk',[StockAdjustmentController::class, 'bulkStore']);

        Route::get('cash-boxes', [CashBoxController::class, 'show']);
        Route::post('cash-boxes', [CashBoxController::class, 'store']);
        Route::get('cash-boxes/transactions', [CashBoxController::class, 'transactions']);
        Route::get('cash-boxes/statistics', [CashBoxController::class, 'statistics']);

        Route::get('customers', [CustomerController::class, 'index']);
        Route::post('customers', [CustomerController::class, 'store']);
        Route::get('customers/{customer}',[CustomerController::class, 'show']);
        Route::put('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
        Route::patch('customers/{customer}/restore',[CustomerController::class, 'restore'])->withTrashed();

        Route::get('sales-invoices', [SalesInvoiceController::class, 'index']);
        Route::post('sales-invoices', [SalesInvoiceController::class, 'store']);
        Route::get('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show']);
        Route::put('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'update']);
        Route::patch('sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel']);

        Route::get('customer-return-invoices',[CustomerReturnInvoiceController::class, 'index']);
        Route::post('customer-return-invoices',[CustomerReturnInvoiceController::class, 'store']);
        Route::get('customer-return-invoices/{customerReturnInvoice}',[CustomerReturnInvoiceController::class, 'show']);
        Route::patch('customer-return-invoices/{customerReturnInvoice}/cancel',[CustomerReturnInvoiceController::class, 'cancel']);

        Route::get('supplier-return-invoices',[SupplierReturnInvoiceController::class, 'index']);
        Route::post('supplier-return-invoices',[SupplierReturnInvoiceController::class, 'store']);
        Route::get('supplier-return-invoices/{supplierReturnInvoice}',[SupplierReturnInvoiceController::class, 'show']);
        Route::patch('supplier-return-invoices/{supplierReturnInvoice}/cancel',[SupplierReturnInvoiceController::class, 'cancel']);

        Route::get('dashboard/header', [DashboardController::class, 'header']);
        Route::get('dashboard/cards', [DashboardController::class, 'cards']);
        Route::get('dashboard/weekly-revenue',[DashboardController::class, 'weeklyRevenue']);
        Route::get('dashboard/transactions',[DashboardController::class, 'transactions']);

        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/top-products', [ReportController::class, 'topProducts']);
        Route::get('reports/profit', [ReportController::class, 'profit']);
        Route::get('reports/supplier-prices', [ReportController::class, 'supplierPrices']);
        Route::get('reports/inventory-value', [ReportController::class, 'inventoryValue']);
        Route::get('reports/stock-health', [ReportController::class, 'stockHealth']);
    });
