
<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeGroupController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\EtiketController;
use App\Http\Controllers\Admin\FooterTitleController;
use App\Http\Controllers\Admin\FooterTitleLinkController;
use App\Http\Controllers\Admin\GiftStructureController;
use App\Http\Controllers\Admin\GoldSummaryController;
use App\Http\Controllers\Admin\HeaderLinkController;
use App\Http\Controllers\Admin\IndexBannerController;
use App\Http\Controllers\Admin\IndexButtonController;
use App\Http\Controllers\Admin\InvoiceTemplateController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductSliderButtonController;
use App\Http\Controllers\Admin\ProductSliderController;
use App\Http\Controllers\Admin\QAController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Api\V1\GatewayController;
use Illuminate\Support\Facades\Route;

Route::middleware('panel.ip')->group(function () {
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
        Route::get('blogs', [BlogController::class, 'table'])->name('blogs');
        Route::get('users', [UserController::class, 'table'])->name('users');
        Route::get('roles', [RoleController::class, 'table'])->name('roles');
        Route::get('qas', [QAController::class, 'table'])->name('qas');
        Route::get('index_banners', [IndexBannerController::class, 'table'])->name('index_banners');
        Route::get('index_buttons', [IndexButtonController::class, 'table'])->name('index_buttons');
        Route::get('product_sliders', [ProductSliderController::class, 'table'])->name('product_sliders');
        Route::get('product_slider_buttons/{product_slider}', [ProductSliderButtonController::class, 'table'])->name('product_slider_buttons');
        Route::any('categories', [CategoryController::class, 'table'])->name('categories');
        Route::get('pages', [PageController::class, 'table'])->name('pages');
        Route::get('header_links', [HeaderLinkController::class, 'table'])->name('header_links');
        Route::get('footer_titles', [FooterTitleController::class, 'table'])->name('footer_titles');
        Route::get('footer_title_links/{footer_title}', [FooterTitleLinkController::class, 'table'])->name('footer_title_links');
        Route::any('order', [OrderController::class, 'table'])->name('orders');
        Route::any('products', [ProductController::class, 'table'])->name('products');
        Route::any('products_not_available', [ProductController::class, 'not_available_table'])->name('products_not_available');
        Route::any('products_without_category', [ProductController::class, 'products_without_category_table'])->name('products_without_category');
        Route::any('products_comprehensive', [ProductController::class, 'products_comprehensive_table'])->name('products_comprehensive');
        Route::any('products_comprehensive_not_available', [ProductController::class, 'products_comprehensive_not_available_table'])->name('products_comprehensive_not_available');
        Route::any('products_children_of/{product}', [ProductController::class, 'products_children_of_table'])->name('products_children_of');
        Route::any('attributes', [AttributeController::class, 'table'])->name('attributes');
        Route::any('attribute_groups', [AttributeController::class, 'table'])->name('attribute_groups');
        Route::any('templates', [InvoiceTemplateController::class, 'table'])->name('templates');
        Route::any('discounts', [DiscountController::class, 'table'])->name('discounts');
        Route::any('gift_structures', [GiftStructureController::class, 'table'])->name('gift_structures');
        Route::any('shippings', [ShippingController::class, 'table'])->name('shippings');
        Route::any('shipping_times/{shipping}', [ShippingController::class, 'timesTable'])->name('shipping_times');
        Route::any('gold_summary', [GoldSummaryController::class, 'table'])->name('gold_summary');
    });
    Route::middleware(['auth'])->prefix('product')->name('product.')->group(function () {
        Route::get('etikets/{product}', [EtiketController::class, 'getEtiketsOfProduct'])->name('etikets');
    });
    Route::middleware(['auth'])->prefix('admin')-> group(function (){
        Route::resource('roles', RoleController::class );
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::resource('users', UserController::class );
        Route::resource('admins', AdminController::class );
        Route::resource('categories', CategoryController::class );
        Route::resource('setting_group.settings', SettingController::class );
        Route::delete('setting_group/{setting_group}/settings/{setting}/delete-image/{imageIndex}', [SettingController::class, 'deleteImage'])->name('setting_group.settings.deleteImage');
        Route::resource('pages', PageController::class );
        
        // Product routes - specific routes BEFORE resource route
        Route::get('products/export', [ProductController::class,'export' ])->name('products.export');
        Route::post('products/bulk_update', [ProductController::class,'bulkUpdate' ])->name('products.bulk_update');
        Route::post('products/assign_category', [ProductController::class,'assignCategory' ])->name('products.assign_category');
        Route::post('products/remove-cover/{product}', [ProductController::class,'removeCoverImage' ])->name('products.remove_cover_image');
        Route::post('products/recalculate-discounts', [ProductController::class,'recalculateDiscounts' ])->name('products.recalculate_discounts');
        Route::get('products/ajax/search', [ProductController::class, 'ajaxSearch'])->name('products.ajax.search');
        Route::get('products/search-by-etiket', [ProductController::class, 'searchByEtiketCode'])->name('products.search.by.etiket');
        Route::post('products/{product}/etikets', [EtiketController::class, 'storeForProduct'])->name('products.etikets.store');
        
        Route::resource('products', ProductController::class );
        Route::resource('admin_orders', OrderController::class );
        Route::get('admin_order/print/{order:uuid}', [OrderController::class, 'print'])->name('admin_order.print');
        Route::get('admin_order/cancel/{order}', [OrderController::class, 'cancel'])->name('admin_order.cancel');
        Route::post('admin_order/update/{order}', [OrderController::class, 'updateOrder'])->name('admin_order.update');
        Route::post('update_order_status', [OrderController::class,'updateOrderStatus'] )->name('update_order_status');
        Route::get('admin_order/users/search', [OrderController::class, 'getUsersList'])->name('admin_order.users.search');
        
        // Trash routes
        Route::get('admin_orders_trash', [OrderController::class, 'trash'])->name('admin_orders.trash');
        Route::post('table/orders/trash', [OrderController::class, 'trashTable'])->name('table.orders.trash');
        Route::post('admin_orders/{id}/restore', [OrderController::class, 'restore'])->name('admin_orders.restore');
        Route::delete('admin_orders/{id}/force-delete', [OrderController::class, 'forceDelete'])->name('admin_orders.force_delete');
        
        // Bulk actions
        Route::post('admin_orders/bulk-status', [OrderController::class, 'bulkStatus'])->name('admin_orders.bulk_status');
        Route::post('admin_orders/bulk-delete', [OrderController::class, 'bulkDelete'])->name('admin_orders.bulk_delete');
        Route::get('admin_order/users/{user}/addresses', [OrderController::class, 'getUserAddresses'])->name('admin_order.users.addresses');
        Route::post('admin_orders/clear-cache', [OrderController::class, 'clearCache'])->name('admin_orders.clear_cache');
        Route::post('admin_orders/get-etikets', [OrderController::class, 'getEtiketsFromAccounting'])->name('admin_orders.get_etikets');
        Route::get('products_not_available', [ProductController::class,'notAvailable' ])->name('products.products_not_available');
        Route::get('products_without_category', [ProductController::class,'withoutCategory' ])->name('products.product_without_category');
        Route::get('products_comprehensive', [ProductController::class,'productsComprehensive' ])->name('products.products_comprehensive');
        Route::get('products_comprehensive_not_available', [ProductController::class,'productsComprehensiveNotAvailable' ])->name('products.products_comprehensive_not_available');
        Route::get('products_children_of/{product}', [ProductController::class,'productsChildrenOf' ])->name('products.products_children_of');
        Route::resource('index_banners', IndexBannerController::class );
        Route::resource('index_buttons', IndexButtonController::class );
        Route::resource('qas', QAController::class );
        Route::resource('product_sliders', ProductSliderController::class );
        Route::resource('product_sliders.product_slider_buttons', ProductSliderButtonController::class );
        Route::resource('header_links', HeaderLinkController::class );
        Route::resource('footer_titles', FooterTitleController::class );
        Route::resource('footer_title.footer_title_links', FooterTitleLinkController::class );
        Route::get('etiket_search', [EtiketController::class, 'search'])->name('etiket_search');
        Route::post('store_comprehensive_product', [ProductController::class, 'storeComprehensiveProduct'])->name('comprehensive_product.store');
        Route::post('comprehensive_product/add', [ProductController::class, 'addProductToComprehensive'])->name('comprehensive_product.add');
        Route::post('comprehensive_product/remove', [ProductController::class, 'removeProductFromComprehensive'])->name('comprehensive_product.remove');
    
        Route::resource('attributes', AttributeController::class );
        Route::resource('attribute_groups', AttributeGroupController::class );
        Route::get('attribute_groups/api/list', [AttributeGroupController::class, 'apiList'])->name('attribute_groups.api_list');
        Route::resource('invoice_templates', InvoiceTemplateController::class );
        Route::resource('discounts', DiscountController::class );
        Route::resource('gift_structures', GiftStructureController::class );
        Route::resource('shippings', ShippingController::class );
        Route::resource('blogs', BlogController::class );
        
        // Shipping times nested routes
        Route::get('shippings/{shipping}/times', [ShippingController::class, 'times'])->name('shippings.times');
        Route::post('shippings/{shipping}/times', [ShippingController::class, 'storeTime'])->name('shippings.times.store');
        Route::put('shippings/{shipping}/times/{time}', [ShippingController::class, 'updateTime'])->name('shippings.times.update');
        Route::delete('shippings/{shipping}/times/{time}', [ShippingController::class, 'destroyTime'])->name('shippings.times.destroy');
    
        Route::post('load_attribute_group',[AttributeController::class,'loadAttributeGroup'])->name('load_attribute_group');
    
        Route::get('visit',[VisitController::class,'index'])->name('visit.index');
        Route::post('visit/clear-bots',[VisitController::class,'clearBotVisits'])->name('visit.clear.bots');
        Route::get('gold_summary',[GoldSummaryController::class,'index'])->name('gold_summary.index');
        Route::get('gold_summary/{order}',[GoldSummaryController::class,'show'])->name('gold_summary.show');
    
    });
});

Route::get('order/print/{order:uuid}', [OrderController::class, 'print'])->name('order.print');
Route::get('/payment/request', [GatewayController::class, 'pay'])->name('payment.request');
Route::any('/payment/callback', [GatewayController::class, 'callback2'])->name('payment.callback');

// Sitemap
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);

// Product RSS Feed
Route::get('/products/feed.xml', [\App\Http\Controllers\ProductFeedController::class, 'index'])->name('products.feed');

// Torob API
Route::get('/api/torob/products', [\App\Http\Controllers\TorobController::class, 'getProducts'])->name('torob.products');

// TEST ROUTE - Remove after testing
Route::get('/test-thank-you/{orderId}', function($orderId) {
    $order = \App\Models\Order::with(['gateway', 'shipping', 'user', 'address'])->find($orderId);
    
    if (!$order) {
        return abort(404, 'Order not found');
    }
    
    return view('thank-you.index', compact('order'));
})->name('test.thank-you');

