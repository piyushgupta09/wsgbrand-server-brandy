<?php

namespace Fpaipl\Brandy;

use Illuminate\Support\ServiceProvider;

class BrandyServiceProvider extends ServiceProvider
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
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadMigrationsFrom(__DIR__.'/path/to/migrations');
        // $this->loadTranslationsFrom(__DIR__.'/path/to/translations', 'brandy');
        // $this->loadJsonTranslationsFrom(__DIR__.'/path/to/translations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'brandy');
        $this->loadViewComponentsAs('brandy', []);
    }
}
