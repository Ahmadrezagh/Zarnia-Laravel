<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FavoriteProductController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShoppingCartController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::middleware(ForceJsonResponse::class)->group(function () {
    Route::prefix('v1')->group(function () {
        //Authentication
        Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
        Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

        //Public Routes
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
            Route::post('/auth/logout', [AuthController::class, 'logout']);

            //Products
            Route::get('favorite_products/{product}', [FavoriteProductController::class, 'addOrRemove']);
            Route::get('favorite_products', [FavoriteProductController::class, 'list']);

            Route::get('shopping_cart/plus/{product}', [ShoppingCartController::class, 'plus']);
            Route::get('shopping_cart/minus/{product}', [ShoppingCartController::class, 'minus']);
            Route::get('shopping_cart', [ShoppingCartController::class, 'index']);
        });
    });
});
