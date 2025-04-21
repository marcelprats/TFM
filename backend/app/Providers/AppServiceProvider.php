<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Definim el morphMap per les relacions polimòrfiques:
        //  - 'user'   → App\Models\User
        //  - 'vendor' → App\Models\Vendor
        Relation::morphMap([
            'user'   => User::class,
            'vendor' => Vendor::class,
        ]);

        Relation::requireMorphMap();
    }
}
