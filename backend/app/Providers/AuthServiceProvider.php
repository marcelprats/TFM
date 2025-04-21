<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;

// Models
use App\Models\Cart;
use App\Models\Order;
use App\Models\Reserve;
use App\Models\Producte;
use App\Models\Botiga;
use App\Models\Vendor;
use App\Models\ImportRecord;

// Policies
use App\Policies\CartPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ReservePolicy;
use App\Policies\ProductePolicy;
use App\Policies\BotigaPolicy;
use App\Policies\VendorPolicy;
use App\Policies\ImportRecordPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapatge entre Models i Policies.
     *
     * Afegim aquí tots els teus models i les corresponents policies:
     * - Cart (carret)
     * - Order (comandes)
     * - Reserve (reserves)
     * - Producte (productes)
     * - Botiga (botigues)
     * - Vendor (venedors)
     * - ImportRecord (importacions)
     */
    protected $policies = [
        Cart::class         => CartPolicy::class,
        Order::class        => OrderPolicy::class,
        Reserve::class      => ReservePolicy::class,
        Producte::class     => ProductePolicy::class,
        Botiga::class       => BotigaPolicy::class,
        Vendor::class       => VendorPolicy::class,
        ImportRecord::class => ImportRecordPolicy::class,
    ];

    /**
     * Registra les policies i defineix gates globals si cal.
     */
    public function boot()
    {
        $this->registerPolicies();

        Relation::morphMap([
            'user'   => \App\Models\User::class,
            'vendor' => \App\Models\Vendor::class,
        ]);
        Relation::requireMorphMap();

        //
        // Exemple de Gate genèrica, si vols bloquejar tot l'accés per defecte:
        // Gate::before(function ($user, $ability) {
        //     return $user->is_suspended ? false : null;
        // });
    }
}
