<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\BackOffice\AgentAuthController;
use App\Http\Controllers\BackOffice\BackOfficeController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — NutriSport
|--------------------------------------------------------------------------
*/

// ── Public Catalog Feeds ─────────────────────────────────────────────────
Route::get('/feeds/{format}', [FeedController::class, 'serve'])
    ->where('format', 'json|xml');

// ── BackOffice Routes ─────────────────────────────────────────────────────
Route::prefix('backoffice')->group(function () {

    // Auth (no guard)
    Route::post('login',  [AgentAuthController::class, 'login']);

    // Authenticated agent routes
    Route::middleware('auth:api_agent')->group(function () {
        Route::post('logout', [AgentAuthController::class, 'logout']);
        Route::get('me',      [AgentAuthController::class, 'me']);

        // Orders — requires 'view_orders' permission
        Route::get('orders', [BackOfficeController::class, 'orders'])
            ->middleware('agent.permission:view_orders');

        // Products — requires 'create_products' permission
        Route::post('products', [BackOfficeController::class, 'createProduct'])
            ->middleware('agent.permission:create_products');
    });
});

// ── Site-scoped routes ────────────────────────────────────────────────────
Route::prefix('{site}')
    ->middleware('resolve.site')
    ->group(function () {

        // ── Auth (no guard required) ──────────────────────────
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        // ── Catalog (public) ──────────────────────────────────
        Route::get('products',      [ProductController::class, 'index']);
        Route::get('products/{id}', [ProductController::class, 'show']);

        // ── Cart (public — no auth) ───────────────────────────
        Route::get('cart',                  [CartController::class, 'index']);
        Route::post('cart',                 [CartController::class, 'add']);
        Route::delete('cart/{productId}',   [CartController::class, 'remove']);
        Route::delete('cart',               [CartController::class, 'clear']);

        // ── Authenticated customer routes ─────────────────────
        Route::middleware('auth:api')->group(function () {
            // Profile
            Route::get('me',      [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('logout', [AuthController::class, 'logout']);

            // Orders
            Route::get('orders',      [OrderController::class, 'index']);
            Route::get('orders/{id}', [OrderController::class, 'show']);
            Route::post('orders',     [OrderController::class, 'store']);
        });
    });
