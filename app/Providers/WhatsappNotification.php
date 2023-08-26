<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class WhatsappNotification extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('App\Service\WhatsappServiceInterface','App\Service\WhatsappNotificationService');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
