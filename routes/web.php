
<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeGroupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EtiketController;
use App\Http\Controllers\Admin\FooterTitleController;
use App\Http\Controllers\Admin\FooterTitleLinkController;
use App\Http\Controllers\Admin\HeaderLinkController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\V1\GatewayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('home');
});

Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');

    Route::get('/categories/{category}/complementary-products', [CategoryController::class, 'getComplementaryProducts']);
    Route::get('/categories/{category}/related-products', [CategoryController::class, 'getRelatedProducts']);
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

});
Route::middleware(['auth'])->prefix('table')->name('table.')->group(function () {
    Route::get('admins', [AdminController::class, 'table'])->name('admins');
    Route::get('users', [UserController::class, 'table'])->name('users');
    Route::get('roles', [RoleController::class, 'table'])->name('roles');
    Route::get('categories', [CategoryController::class, 'table'])->name('categories');
    Route::get('pages', [PageController::class, 'table'])->name('pages');
    Route::get('header_links', [HeaderLinkController::class, 'table'])->name('header_links');
    Route::get('footer_titles', [FooterTitleController::class, 'table'])->name('footer_titles');
    Route::get('footer_title_links/{footer_title}', [FooterTitleLinkController::class, 'table'])->name('footer_title_links');
    Route::any('order', [OrderController::class, 'table'])->name('orders');
    Route::any('products', [ProductController::class, 'table'])->name('products');
    Route::any('products_not_available', [ProductController::class, 'not_available_table'])->name('products_not_available');
    Route::any('products_without_category', [ProductController::class, 'products_without_category_table'])->name('products_without_category');
    Route::any('products_comprehensive', [ProductController::class, 'products_comprehensive_table'])->name('products_comprehensive');
    Route::any('attributes', [AttributeController::class, 'table'])->name('attributes');
    Route::any('attribute_groups', [AttributeController::class, 'table'])->name('attribute_groups');
});
Route::middleware(['auth'])->prefix('product')->name('product.')->group(function () {
    Route::get('etikets/{product}', [EtiketController::class, 'getEtiketsOfProduct'])->name('etikets');
});
Route::middleware(['auth'])->prefix('admin')-> group(function (){
    Route::resource('roles', RoleController::class );
    Route::resource('users', UserController::class );
    Route::resource('admins', AdminController::class );
    Route::resource('categories', CategoryController::class );
    Route::resource('setting_group.settings', SettingController::class );
    Route::resource('pages', PageController::class );
    Route::resource('products', ProductController::class );
    Route::resource('admin_orders', OrderController::class );
    Route::post('update_order_status', [OrderController::class,'updateOrderStatus'] )->name('update_order_status');
    Route::post('products/bulk_update', [ProductController::class,'bulkUpdate' ])->name('products.bulk_update');
    Route::post('products/assign_category', [ProductController::class,'assignCategory' ])->name('products.assign_category');
    Route::get('products_not_available', [ProductController::class,'notAvailable' ])->name('products.products_not_available');
    Route::get('products_without_category', [ProductController::class,'withoutCategory' ])->name('products.product_without_category');
    Route::get('products_comprehensive', [ProductController::class,'productsComprehensive' ])->name('products.products_comprehensive');
    Route::resource('header_links', HeaderLinkController::class );
    Route::resource('footer_titles', FooterTitleController::class );
    Route::resource('footer_title.footer_title_links', FooterTitleLinkController::class );
    Route::get('etiket_search', [EtiketController::class, 'search'])->name('etiket_search');
    Route::post('store_comprehensive_product', [ProductController::class, 'storeComprehensiveProduct'])->name('comprehensive_product.store');

    Route::resource('attributes', AttributeController::class );
    Route::resource('attribute_groups', AttributeGroupController::class );


    Route::post('load_attribute_group',[AttributeController::class,'loadAttributeGroup'])->name('load_attribute_group');
});
Route::get('/payment/request', [GatewayController::class, 'request'])->name('payment.request');
Route::post('/payment/callback', [GatewayController::class, 'callback'])->name('payment.callback');
