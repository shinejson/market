<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdCampaignController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminPhase3Controller;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SellerOrderController;
use App\Http\Controllers\Api\SellerPublicApiController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WebhookController;
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
    Route::post('/ads/click/{impression}', [MarketController::class, 'click']);
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

        Route::get('/domains', [DomainController::class, 'index']);
        Route::post('/domains', [DomainController::class, 'store']);
        Route::post('/domains/{domain}/verify', [DomainController::class, 'verify']);
        Route::delete('/domains/{domain}', [DomainController::class, 'destroy']);

        Route::get('/ads', [AdCampaignController::class, 'index']);
        Route::post('/ads', [AdCampaignController::class, 'store']);
        Route::patch('/ads/{campaign}', [AdCampaignController::class, 'update']);
        Route::post('/ads/fund', [AdCampaignController::class, 'fund']);

        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiKeyController::class, 'store']);
        Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);

        Route::get('/webhooks', [WebhookController::class, 'index']);
        Route::post('/webhooks', [WebhookController::class, 'store']);
        Route::delete('/webhooks/{endpoint}', [WebhookController::class, 'destroy']);
        Route::get('/webhooks/catalog', [WebhookController::class, 'catalog']);
        Route::get('/webhooks/{endpoint}/deliveries', [WebhookController::class, 'deliveries']);
        Route::post('/webhooks/deliveries/{delivery}/replay', [WebhookController::class, 'replay']);

        Route::get('/ai/settings', [AiController::class, 'settings']);
        Route::patch('/ai/settings', [AiController::class, 'updateSettings']);
        Route::post('/ai/describe', [AiController::class, 'describe']);
        Route::post('/ai/categorize', [AiController::class, 'categorize']);
        Route::get('/ai/generations', [AiController::class, 'generations']);
        Route::post('/ai/generations/{generation}/review', [AiController::class, 'reviewGeneration']);
        Route::post('/ai/categorizations/{categorization}/accept', [AiController::class, 'acceptCategory']);
        Route::get('/ai/insights', [AiController::class, 'insights']);
        Route::get('/ai/usage', [AiController::class, 'usage']);

        Route::get('/analytics', [AnalyticsController::class, 'tenant']);
    });

    Route::post('/devices', [DeviceTokenController::class, 'store']);
    Route::delete('/devices', [DeviceTokenController::class, 'destroy']);

    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/tenants', [AdminController::class, 'tenants']);
        Route::patch('/tenants/{tenant}', [AdminController::class, 'updateTenant']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/metrics', [AdminController::class, 'metrics']);
        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('/analytics', [AnalyticsController::class, 'platform']);
        Route::get('/insights', [AnalyticsController::class, 'platformInsights']);
        Route::get('/domains', [AdminPhase3Controller::class, 'domains']);
        Route::post('/domains/{domain}/verify', [AdminPhase3Controller::class, 'forceVerifyDomain']);
        Route::delete('/domains/{domain}', [AdminPhase3Controller::class, 'forceRemoveDomain']);
        Route::get('/ads', [AdminPhase3Controller::class, 'ads']);
        Route::get('/ai-costs', [AdminPhase3Controller::class, 'aiCosts']);
        Route::get('/webhooks/health', [AdminPhase3Controller::class, 'webhookHealth']);
    });
});

Route::prefix('seller/v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/products', [SellerPublicApiController::class, 'products'])->middleware('seller.api:products.read');
    Route::get('/orders', [SellerPublicApiController::class, 'orders'])->middleware('seller.api:orders.read');
    Route::post('/orders/{order}/fulfill', [SellerPublicApiController::class, 'fulfill'])->middleware('seller.api:orders.fulfill');
    Route::patch('/variants/{variant}/inventory', [SellerPublicApiController::class, 'inventory'])->middleware('seller.api:inventory.write');
    Route::get('/settlements', [SellerPublicApiController::class, 'settlements'])->middleware('seller.api:settlements.read');
});
