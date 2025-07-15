<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FavoriteProductController;
use App\Http\Controllers\Api\V1\GatewayController;
use App\Http\Controllers\Api\V1\InitController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProvinceController;
use App\Http\Controllers\Api\V1\ShippingController;
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
        Route::get('init', [InitController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::get('provinces', [ProvinceController::class, 'index']);
        Route::get('provinces/{province_id}', [ProvinceController::class, 'show']);

        Route::get('shippings', [ShippingController::class, 'index']);
        Route::get('gateways', [GatewayController::class, 'index']);
        Route::get('blogs', [BlogController::class, 'index']);
        Route::get('blogs/{blog}', [GatewayController::class, 'show']);
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
            Route::get('shopping_cart/remove/{product}', [ShoppingCartController::class, 'remove']);
            Route::get('shopping_cart', [ShoppingCartController::class, 'index']);
            Route::get('profile', [ProfileController::class, 'index']);
            Route::post('profile', [ProfileController::class, 'update']);
            Route::resource('addresses', AddressController::class);
        });
    });
});
