<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class CartPolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function view($user, Cart $cart): bool
    {
        return $cart->owner_id === $user->id
            && $cart->owner_type === get_class($user);
    }

    public function addItem($user): bool
    {
        return $user !== null;
    }

    public function update($user, CartItem $item): bool
    {
        return $item->cart->owner_id === $user->id
            && $item->cart->owner_type === get_class($user);
    }

    public function delete($user, CartItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function deleteAny($user): bool
    {
        return $user !== null;
    }

    public function checkout($user, Cart $cart): bool
    {
        if (! $this->view($user, $cart)) {
            return false;
        }

        return $cart->cartItems()->where('selected', true)->exists();
    }
}
