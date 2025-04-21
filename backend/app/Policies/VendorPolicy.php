<?php

namespace App\Policies;

use App\Models\Vendor;
use Illuminate\Foundation\Auth\User as Authenticatable;

class VendorPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true; // públic
    }

    public function view(Authenticatable $user, Vendor $vendor): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Vendor $vendor): bool
    {
        // només el mateix venedor pot editar el seu perfil
        return $user instanceof Vendor && $user->id === $vendor->id;
    }

    public function delete(Authenticatable $user, Vendor $vendor): bool
    {
        return $this->update($user, $vendor);
    }
}
