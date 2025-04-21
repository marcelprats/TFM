<?php

namespace App\Policies;

use App\Models\Botiga;
use App\Models\User;
use App\Models\Vendor;

class BotigaPolicy
{
    public function viewAny(User|Vendor $user): bool
    {
        return true;
    }

    public function view(User|Vendor $user, Botiga $botiga): bool
    {
        return true;
    }

    public function create(User|Vendor $user): bool
    {
        return $user instanceof Vendor;
    }

    public function update(User|Vendor $user, Botiga $botiga): bool
    {
        return $user instanceof Vendor && $user->id === $botiga->vendor_id;
    }

    public function delete(User|Vendor $user, Botiga $botiga): bool
    {
        return $user instanceof Vendor && $user->id === $botiga->vendor_id;
    }
}
