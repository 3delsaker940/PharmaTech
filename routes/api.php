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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SupplierReturnInvoiceController;
use App\Http\Controllers\WeatherDrivenInventoryController;
use App\Http\Controllers\PharmacistController;
use App\Http\Controllers\DrugInteractionController;
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
        Route::get('/products', [InventoryController::class, 'getAllPharmacyProducts'])
            ->middleware('permission:view-inventory');
        Route::get('/{productId}/stock-batches', [InventoryController::class, 'getProductStockBatches'])
            ->middleware('permission:view-inventory');
        Route::get('/category/{categoryId}/products', [InventoryController::class, 'getProductsByCategory'])
            ->middleware('permission:view-inventory');
    });

Route::prefix('products')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('{product}/batches/available', [ProductController::class, 'availableBatches'])
            ->middleware('permission:view-products');
        Route::get('low-stock', [ProductController::class, 'lowStock'])
            ->middleware('permission:view-products');
        Route::get('barcode/{barcode}', [ProductController::class, 'lookupByBarcode'])
            ->middleware('permission:view-products');
        Route::get('', [ProductController::class, 'index'])
            ->middleware('permission:view-products');
        Route::post('', [ProductController::class, 'store'])
            ->middleware('permission:create-products');
        Route::get('{product}', [ProductController::class, 'show'])
            ->middleware('permission:view-products');
        Route::put('{product}', [ProductController::class, 'update'])
            ->middleware('permission:update-products');
        Route::delete('{product}', [ProductController::class, 'destroy'])
            ->middleware('permission:delete-products');
        Route::patch('{product}/restore', [ProductController::class, 'restore'])
            ->middleware('permission:restore-products')
            ->withTrashed();

        Route::get('/{product}/medical-info', [ProductMedicalInfoController::class, 'show'])
            ->middleware('permission:view-products');
        Route::put('{product}/medical-info', [ProductMedicalInfoController::class, 'upsert'])
            ->middleware('permission:update-products');
        Route::delete('{product}/medical-info', [ProductMedicalInfoController::class, 'destroy'])
            ->middleware('permission:delete-products');
    });

Route::prefix('suppliers')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('', [SupplierController::class, 'index'])
            ->middleware('permission:view-suppliers');
        Route::post('', [SupplierController::class, 'store'])
            ->middleware('permission:create-suppliers');
        Route::get('{supplier}', [SupplierController::class, 'show'])
            ->middleware('permission:view-suppliers');
        Route::put('{supplier}', [SupplierController::class, 'update'])
            ->middleware('permission:update-suppliers');
        Route::delete('{supplier}', [SupplierController::class, 'destroy'])
            ->middleware('permission:delete-suppliers');
        Route::patch('{supplier}/restore', [SupplierController::class, 'restore'])
            ->middleware('permission:restore-suppliers')
            ->withTrashed();
    });

Route::get('categories', [CategoryController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:view-categories']);
Route::get('units', [UnitController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:view-units']);
Route::get('companies', [CompanyController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:view-companies']);
Route::get('companies/{company}', [CompanyController::class, 'show'])
    ->middleware(['auth:sanctum', 'permission:view-companies']);

Route::middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('categories/{category}', [CategoryController::class, 'show'])
            ->middleware('permission:view-categories');

        Route::get('supplier-debts', [SupplierDebtController::class, 'index'])
            ->middleware('permission:view-supplier-debts');
        Route::get('supplier-debts/{supplierDebt}', [SupplierDebtController::class, 'show'])
            ->middleware('permission:view-supplier-debts');
        Route::post('supplier-debts/{supplierDebt}/pay', [SupplierDebtController::class, 'pay'])
            ->middleware('permission:pay-supplier-debts');

        Route::get('customer-debts', [CustomerDebtController::class, 'index'])
            ->middleware('permission:view-customer-debts');
        Route::get('customer-debts/{customerDebt}', [CustomerDebtController::class, 'show'])
            ->middleware('permission:view-customer-debts');
        Route::post('customer-debts/{customerDebt}/pay', [CustomerDebtController::class, 'pay'])
            ->middleware('permission:pay-customer-debts');


        Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])
            ->middleware('permission:view-purchase-invoices');
        Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])
            ->middleware('permission:create-purchase-invoices');
        Route::get('purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])
            ->middleware('permission:view-purchase-invoices');
        Route::put('purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'update'])
            ->middleware('permission:update-purchase-invoices');
        Route::patch('purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])
            ->middleware('permission:cancel-purchase-invoices');

        Route::get('stock-batches', [StockBatchController::class, 'index'])
            ->middleware('permission:view-stock');
        Route::get('stock-batches/{stockBatch}', [StockBatchController::class, 'show'])
            ->middleware('permission:view-stock');
        Route::patch('stock-batches/{stockBatch}/mark-expired', [StockBatchController::class, 'markExpired'])
            ->middleware('permission:manage-stock');

        Route::get('stock-movements', [StockMovementController::class, 'index'])
            ->middleware('permission:view-stock');
        Route::get('stock-movements/{stockMovement}', [StockMovementController::class, 'show'])
            ->middleware('permission:view-stock');

        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])
            ->middleware('permission:view-stock');
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])
            ->middleware('permission:manage-stock');
        Route::post('stock-adjustments/bulk', [StockAdjustmentController::class, 'bulkStore'])
            ->middleware('permission:manage-stock');

        Route::get('cash-boxes', [CashBoxController::class, 'show'])
            ->middleware('permission:view-cash-box');
        Route::post('cash-boxes', [CashBoxController::class, 'store'])
            ->middleware('permission:manage-cash-box');
        Route::get('cash-boxes/transactions', [CashBoxController::class, 'transactions'])
            ->middleware('permission:view-cash-box');
        Route::get('cash-boxes/statistics', [CashBoxController::class, 'statistics'])
            ->middleware('permission:view-cash-box');

        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware('permission:view-customers');
        Route::post('customers', [CustomerController::class, 'store'])
            ->middleware('permission:create-customers');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:view-customers');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:update-customers');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:delete-customers');
        Route::patch('customers/{customer}/restore', [CustomerController::class, 'restore'])
            ->middleware('permission:restore-customers')
            ->withTrashed();

        Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])
            ->middleware('permission:view-sales-invoices');
        Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])
            ->middleware('permission:create-sales-invoices');
        Route::get('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])
            ->middleware('permission:view-sales-invoices');
        Route::put('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'update'])
            ->middleware('permission:update-sales-invoices');
        Route::patch('sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel'])
            ->middleware('permission:cancel-sales-invoices');

        Route::get('customer-return-invoices', [CustomerReturnInvoiceController::class, 'index'])
            ->middleware('permission:view-customer-returns');
        Route::post('customer-return-invoices', [CustomerReturnInvoiceController::class, 'store'])
            ->middleware('permission:create-customer-returns');
        Route::get('customer-return-invoices/{customerReturnInvoice}', [CustomerReturnInvoiceController::class, 'show'])
            ->middleware('permission:view-customer-returns');
        Route::patch('customer-return-invoices/{customerReturnInvoice}/cancel', [CustomerReturnInvoiceController::class, 'cancel'])
            ->middleware('permission:cancel-customer-returns');

        Route::get('supplier-return-invoices', [SupplierReturnInvoiceController::class, 'index'])
            ->middleware('permission:view-supplier-returns');
        Route::post('supplier-return-invoices', [SupplierReturnInvoiceController::class, 'store'])
            ->middleware('permission:create-supplier-returns');
        Route::get('supplier-return-invoices/{supplierReturnInvoice}', [SupplierReturnInvoiceController::class, 'show'])
            ->middleware('permission:view-supplier-returns');
        Route::patch('supplier-return-invoices/{supplierReturnInvoice}/cancel', [SupplierReturnInvoiceController::class, 'cancel'])
            ->middleware('permission:cancel-supplier-returns');

        Route::get('dashboard/header', [DashboardController::class, 'header'])
            ->middleware('permission:view-dashboard');
        Route::get('dashboard/cards', [DashboardController::class, 'cards'])
            ->middleware('permission:view-dashboard');
        Route::get('dashboard/weekly-revenue', [DashboardController::class, 'weeklyRevenue'])
            ->middleware('permission:view-dashboard');
        Route::get('dashboard/transactions', [DashboardController::class, 'transactions'])
            ->middleware('permission:view-dashboard');

        Route::get('reports/sales', [ReportController::class, 'sales'])
            ->middleware('permission:view-reports');
        Route::get('reports/top-products', [ReportController::class, 'topProducts'])
            ->middleware('permission:view-reports');
        Route::get('reports/profit', [ReportController::class, 'profit'])
            ->middleware('permission:view-reports');
        Route::get('reports/supplier-prices', [ReportController::class, 'supplierPrices'])
            ->middleware('permission:view-reports');
        Route::get('reports/inventory-value', [ReportController::class, 'inventoryValue'])
            ->middleware('permission:view-reports');
        Route::get('reports/stock-health', [ReportController::class, 'stockHealth'])
            ->middleware('permission:view-reports');
        Route::get('/inventory/predict-needs', [WeatherDrivenInventoryController::class, 'predictInventoryNeeds'])
            ->middleware('permission:view-inventory-predictions');
        Route::post('/inventory/check-drug-interactions', [DrugInteractionController::class, 'checkInteractions'])
            ->middleware('permission:check-drug-interactions');
        Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

        Route::get('/pharmacists', [PharmacistController::class, 'index'])
            ->middleware('permission:view-pharmacists');
        Route::post('/pharmacists', [PharmacistController::class, 'store'])
            ->middleware('permission:create-pharmacists');
        Route::get('/pharmacists/{id}', [PharmacistController::class, 'show'])
            ->middleware('permission:view-pharmacists');
        Route::put('/pharmacists/{id}', [PharmacistController::class, 'update'])
            ->middleware('permission:update-pharmacists');
        Route::delete('/pharmacists/{id}', [PharmacistController::class, 'destroy'])
            ->middleware('permission:delete-pharmacists');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });
