<?php

namespace App\Providers;

use App\Http\Middleware\Cors;
use Illuminate\Routing\Router;
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
        // Registrar middleware global
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('api', Cors::class);
    }
}
