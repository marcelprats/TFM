<?php

namespace App\Policies;

use App\Models\Reserve;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ReservePolicy
{
    public function view(Authenticatable $user, Reserve $reserve): bool
    {
        if ($user instanceof \App\Models\User) {
            return $reserve->buyer_id === $user->id
                && $reserve->buyer_type === $user->getMorphClass();
        }
        if ($user instanceof \App\Models\Vendor) {
            return $reserve->botiga_id
                && $reserve->botiga->vendor_id === $user->id;
        }
        return false;
    }

    public function create(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\User;
    }

    public function update(Authenticatable $user, Reserve $reserve): bool
    {
        // Per exemple, venta de depòsits: només el vendor de la botiga
        return $this->view($user, $reserve) && $user instanceof \App\Models\Vendor;
    }

    public function delete(Authenticatable $user, Reserve $reserve): bool
    {
        return $reserve->status === 'pending'
            && $this->view($user, $reserve);
    }
}
