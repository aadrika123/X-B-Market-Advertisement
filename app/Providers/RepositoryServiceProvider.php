<?php

namespace App\Providers;

use App\Repositories\Markets\iMarketRepo;
use App\Repositories\Markets\MarketRepo;
use App\Repositories\SelfAdvets\iSelfAdvetRepo;
use App\Repositories\SelfAdvets\SelfAdvetRepo;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(iSelfAdvetRepo::class, SelfAdvetRepo::class);
        $this->app->bind(iMarketRepo::class, MarketRepo::class);
    }
}
