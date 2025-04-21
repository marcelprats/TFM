<?php

namespace App\Policies;

use App\Models\CartItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;

class CartItemPolicy
{
    use HandlesAuthorization;

    public function view(Authenticatable $user, CartItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function update(Authenticatable $user, CartItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function delete(Authenticatable $user, CartItem $item): bool
    {
        return $this->owns($user, $item);
    }

    protected function owns(Authenticatable $user, CartItem $item): bool
    {
        if (!$item->relationLoaded('cart')) {
            $item->load('cart');
        }
    
        if (!$item->cart) {
            Log::warning('Cart no carregat al CartItemPolicy', [
                'item_id' => $item->id,
                'user_id' => $user->id,
            ]);
            return false;
        }
    
        $expectedType = $this->getMorphAlias($user);
    
        return $item->cart->owner_id === $user->id &&
               $item->cart->owner_type === $expectedType;
    }
    

    protected function getMorphAlias(Authenticatable $user): string
    {
        return array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
    }
}
