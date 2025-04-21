<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\User;
use App\Models\Vendor;

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
        Relation::morphMap([
            'user' => \App\Models\User::class,
            'vendor' => \App\Models\Vendor::class,
        ]);

        Relation::requireMorphMap(); // Opcional, força l'ús del map i evita errors silenciosos
    }
}
