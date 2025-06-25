<?php

namespace App\Providers;

use App\Models\Etiket;
use App\Models\SettingGroup;
use App\Observers\EtiketObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
    }
}
