<?php

namespace App\Providers;

use App\Models\Representation;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Add this import
use App\Models\User;
use App\Observers\RepresentationObserver;
use App\Observers\Subadmin\Merchant\UserObserver;
use App\Repositories\Admin\Interfaces\GovernanceLayerRepositoryInterface;
use App\Repositories\Admin\Eloquent\GovernanceLayerRepository;
use App\Repositories\Merchant\Interfaces\PaymentRepositoryInterface;;
use App\Repositories\Merchant\Eloquent\PaymentRepository;
use App\Repositories\Admin\Interfaces\AppSettingInterfaces;
use App\Repositories\Admin\Eloquent\AppSettingRepository;
use App\Repositories\Merchant\Interfaces\TeamMemberRepositoryInterface;;
use App\Repositories\Merchant\Eloquent\TeamMemberRepository;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            GovernanceLayerRepositoryInterface::class,
            GovernanceLayerRepository::class
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );

        $this->app->bind(
            TeamMemberRepositoryInterface::class,
            TeamMemberRepository::class
        );


        $this->app->bind(
            AppSettingInterfaces::class,
            AppSettingRepository::class
        );
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Fix for MySQL ONLY_FULL_GROUP_BY error
        if (config('database.default') === 'mysql') {
            DB::statement("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        }

        if (! $this->app->runningInConsole()) {
            // 'key' => 'value'
            $settings = Setting::all('key', 'value')
                ->keyBy('key')
                ->transform(fn($setting) => $setting->value)
                ->toArray();
            config([
               'settings' => $settings
            ]);

            config(['app.name' => config('settings.app_name')]);
        }

        Paginator::useBootstrap();
        User::observe(UserObserver::class);
        Representation::observe(RepresentationObserver::class);
    }
}