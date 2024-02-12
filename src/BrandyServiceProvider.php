<?php

namespace Fpaipl\Brandy;

use Livewire\Livewire;
use Illuminate\Support\ServiceProvider;
use Fpaipl\Brandy\Http\Livewire\PartyAssignRoles;
use Fpaipl\Brandy\Http\Livewire\EmployeeAssignRoles;

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
        $this->loadViewsFrom(__DIR__.'/resources/views', 'brandy');
        $this->loadViewComponentsAs('brandy', []);

        Livewire::component('party-assign-roles', PartyAssignRoles::class);
        Livewire::component('employee-assign-roles', EmployeeAssignRoles::class);

    }
}
