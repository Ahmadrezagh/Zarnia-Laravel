<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\FavoriteProductController;
use App\Http\Controllers\Api\V1\GatewayController;
use App\Http\Controllers\Api\V1\GiftController;
use App\Http\Controllers\Api\V1\IndexBannerController;
use App\Http\Controllers\Api\V1\IndexButtonController;
use App\Http\Controllers\Api\V1\InitController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductSliderController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProvinceController;
use App\Http\Controllers\Api\V1\QAController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\ShoppingCartController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Middleware\CheckTestUserToken;
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
        Route::get('categories/{category}/products', [ProductController::class, 'categoryProducts']);
        Route::get('random_products', [ProductController::class, 'random_products']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::get('products/{product}/related-complementary', [ProductController::class, 'relatedAndComplementary']);
        Route::get('provinces', [ProvinceController::class, 'index']);
        Route::get('provinces/{province_id}', [ProvinceController::class, 'show']);

        Route::get('shippings', [ShippingController::class, 'index']);
        Route::get('gateways', [GatewayController::class, 'index']);
        Route::get('blogs', [BlogController::class, 'index']);
        Route::get('blogs/{blog}', [BlogController::class, 'show']);

        Route::get('qa',[QAController::class,'index']);
        Route::get('product_slider',[ProductSliderController::class,'index']);
        Route::get('index_buttons',[IndexButtonController::class,'index']);
        Route::get('index_banners',[IndexBannerController::class,'index']);
        Route::post('track-visit', [TrackingController::class, 'trackVisit']);
        // Protected routes
        Route::middleware([CheckTestUserToken::class, 'auth:sanctum'])->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
            Route::post('/auth/logout', [AuthController::class, 'logout']);

            //Products
            Route::get('favorite_products/{product}', [FavoriteProductController::class, 'addOrRemove']);
            Route::get('favorite_products', [FavoriteProductController::class, 'list']);

            Route::get('shopping_cart/plus/{product}', [ShoppingCartController::class, 'plus']);
            Route::resource('orders', OrderController::class)->only('store');
            Route::get('shopping_cart/minus/{product}', [ShoppingCartController::class, 'minus']);
            Route::get('shopping_cart/remove/{product}', [ShoppingCartController::class, 'remove']);
            Route::get('shopping_cart', [ShoppingCartController::class, 'index']);
            Route::get('profile', [ProfileController::class, 'index']);
            Route::post('profile', [ProfileController::class, 'update']);
            Route::resource('addresses', AddressController::class);
            Route::resource('orders', OrderController::class)->only('index');
            Route::get('order/{order}/status', [OrderController::class, 'status']);
//            Route::post('order/{order}/update', [OrderController::class, 'updateSnappTransaction']);
//            Route::get('order/{order}/cancel', [OrderController::class, 'cancel']);
            Route::get('order/{order}/settle', [OrderController::class, 'settle']);
            Route::get('order/eligible/{price}', [OrderController::class, 'eligible']);
            Route::post('discount/verify', [DiscountController::class,'verify']);

            Route::get('gifts', [GiftController::class,'index']);
        });
    });
});
