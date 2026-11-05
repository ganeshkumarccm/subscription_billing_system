<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware('jwt.custom')->group(function () {
        Route::get('/me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('/refresh', [\App\Http\Controllers\AuthController::class, 'refresh']);
    });
});

Route::middleware('jwt.custom')->prefix('v1')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'show']);
    Route::get('/merchant', [\App\Http\Controllers\MerchantController::class, 'show']);
    Route::post('/merchant/api-key/regenerate', [\App\Http\Controllers\MerchantController::class, 'regenerateApiKey']);
    Route::get('/plans', [\App\Http\Controllers\CatalogController::class, 'plans']);
    Route::post('/plans', [\App\Http\Controllers\CatalogController::class, 'storePlan']);
    Route::put('/plans/{id}', [\App\Http\Controllers\CatalogController::class, 'updatePlan']);
    Route::get('/customers', [\App\Http\Controllers\CatalogController::class, 'customers']);
    Route::post('/customers', [\App\Http\Controllers\CatalogController::class, 'storeCustomer']);
    Route::put('/customers/{id}', [\App\Http\Controllers\CatalogController::class, 'updateCustomer']);
    Route::get('/customers/{id}', [\App\Http\Controllers\CatalogController::class, 'customer']);
    Route::post('/customers/{customer}/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'store']);
});

Route::middleware(['jwt.custom', 'merchant.owner'])->prefix('v1/merchants/{merchant}')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'show']);
});

Route::prefix('v1')->middleware(['merchant.api_key', 'throttle:usage'])->group(function () {
    Route::post('/usage', [\App\Http\Controllers\UsageController::class, 'store']);
});

Route::prefix('v1/merchants/{merchant}')
    ->middleware(['jwt.custom', 'merchant.owner'])
    ->group(function () {
        Route::post('/subscriptions/{id}/change-plan', [\App\Http\Controllers\SubscriptionController::class, 'changePlan']);
        Route::post('/subscriptions/{id}/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel']);
    });

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
