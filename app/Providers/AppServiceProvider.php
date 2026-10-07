<?php

namespace App\Providers;

use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Observers\MsCompanyObserver;
use App\Observers\MsEmployeeObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        MsCompany::observe(MsCompanyObserver::class);
        MsEmployee::observe(MsEmployeeObserver::class);

        Relation::morphMap([
            'client' => MsClient::class,
            'employee' => MsEmployee::class,
        ]);
    }
}
