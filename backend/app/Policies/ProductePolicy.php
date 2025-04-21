<?php

namespace App\Policies;

use App\Models\Producte;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ProductePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        // tothom pot llistar productes públics, no cal middleware
        return true;
    }

    public function view(Authenticatable $user, Producte $producte): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\Vendor;
    }

    public function update(Authenticatable $user, Producte $producte): bool
    {
        return $user instanceof \App\Models\Vendor
            && $producte->botiga->vendor_id === $user->id;
    }

    public function delete(Authenticatable $user, Producte $producte): bool
    {
        return $this->update($user, $producte);
    }
}
