<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Relations\Relation;

class CartPolicy
{
    use HandlesAuthorization;

    public function viewAny(mixed $user): bool
    {
        return $user !== null;
    }

    public function view(mixed $user, Cart $cart): bool
    {
        return $cart->owner_id === $user->id &&
               $cart->owner_type === $this->getMorphAlias($user);
    }

    public function addItem(mixed $user): bool
    {
        return $user !== null;
    }

    public function update(mixed $user, CartItem $item): bool
    {
        return $item->cart->owner_id === $user->id &&
               $item->cart->owner_type === $this->getMorphAlias($user);
    }

    public function delete(mixed $user, CartItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function deleteAny(mixed $user): bool
    {
        return $user !== null;
    }

    public function checkout(mixed $user, Cart $cart): bool
    {
        if (! $this->view($user, $cart)) {
            return false;
        }

        return $cart->cartItems()->where('selected', true)->exists();
    }

    protected function getMorphAlias(mixed $user): string
    {
        return array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
    }
}
