<?php

namespace App\Providers;

use App\Models\Etiket;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\SettingGroup;
use App\Observers\EtiketObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        if(App::isProduction())
            URL::forceScheme('https');

        try{
            if (Schema::hasTable('setting_groups'))
            {
                $setting_groups = SettingGroup::all();
                View::share('setting_groups', $setting_groups);
            }
        }catch (\Exception $exception){

        }

        Etiket::observe(EtiketObserver::class);
        Product::Observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        
        // Route model binding for Category to use slug instead of id
        Route::bind('category', function ($value) {
            return Category::where('slug', $value)->firstOrFail();
        });
    }
}
