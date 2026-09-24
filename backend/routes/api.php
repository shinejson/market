<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SellerOrderController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::prefix('market')->middleware('throttle:60,1')->group(function () {
    Route::get('/products', [MarketController::class, 'products']);
    Route::get('/products/{slug}', [MarketController::class, 'product']);
    Route::get('/stores', [MarketController::class, 'stores']);
    Route::get('/stores/{slug}', [MarketController::class, 'store']);
    Route::get('/categories', [MarketController::class, 'categories']);
});

Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook']);
Route::get('/payments/mock/pay', [PaymentController::class, 'mockPay']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::post('/tenants/register', [TenantController::class, 'register']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'add']);
        Route::patch('/cart/items/{item}', [CartController::class, 'update']);
        Route::delete('/cart/items/{item}', [CartController::class, 'destroy']);

        Route::post('/checkout/quote', [CheckoutController::class, 'quote']);
        Route::post('/checkout', [CheckoutController::class, 'store']);
    });

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::post('/payments/intent/{orderId}', [PaymentController::class, 'intent']);

    Route::middleware(['tenant', 'role:tenant'])->prefix('tenant')->group(function () {
        Route::get('/', [TenantController::class, 'show']);
        Route::patch('/', [TenantController::class, 'update']);

        Route::get('/stores', [TenantController::class, 'stores']);
        Route::post('/stores', [TenantController::class, 'storeStore']);
        Route::patch('/stores/{store}', [TenantController::class, 'updateStore']);
        Route::delete('/stores/{store}', [TenantController::class, 'destroyStore']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::post('/products/{product}/variants', [ProductController::class, 'storeVariant']);
        Route::patch('/products/{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
        Route::delete('/products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant']);

        Route::post('/products/{product}/images', [ProductController::class, 'storeImage']);
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage']);
        Route::patch('/products/{product}/images', [ProductController::class, 'reorderImages']);

        Route::get('/variants/{variant}/inventory', [InventoryController::class, 'show']);
        Route::patch('/variants/{variant}/inventory', [InventoryController::class, 'update']);
        Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);

        Route::get('/orders', [SellerOrderController::class, 'index']);
        Route::get('/orders/{order}', [SellerOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [SellerOrderController::class, 'updateStatus']);

        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    });

    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/tenants', [AdminController::class, 'tenants']);
        Route::patch('/tenants/{tenant}', [AdminController::class, 'updateTenant']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/metrics', [AdminController::class, 'metrics']);
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    });
});
