<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Relations\Relation;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Qualsevol usuari autenticat pot llistar les seves comandes,
     * i qualsevol vendor pot llistar les comandes de les seves botigues.
     */
    public function viewAny($user): bool
    {
        return $user instanceof User || $user instanceof Vendor;
    }

    /**
     * Veure una comanda.
     */
    public function view($user, Order $order): bool
    {
        $morph = array_search(get_class($user), Relation::morphMap()) ?? get_class($user);
    
        // És el comprador? (sigui User o Vendor)
        if ($order->buyer_id === $user->id && $order->buyer_type === $morph) {
            return true;
        }
    
        // És el venedor de la botiga?
        if ($order->reserve && $order->reserve->botiga) {
            return $order->reserve->botiga->vendor_id === $user->id;
        }
    
        return false;
    }  

    /**
     * Només un User (comprador) pot crear comandes.
     */
    public function create($user): bool
    {
        return $user instanceof User;
    }

    /**
     * Actualitzar una comanda.
     * - El comprador només pot cancel·lar mentre estigui pending.
     * - El vendor pot actualitzar estat de les seves comandes.
     */
    public function update($user, Order $order): bool
    {
        $morph = Relation::getMorphedModel(get_class($user));

        if ($order->buyer_id === $user->id && $order->buyer_type === $morph && $order->status === 'pending') {
            return true;
        }

        return optional($order->reserve->botiga)->vendor_id === $user->id;
    }

    /**
     * Eliminar una comanda.
     * Només el comprador en pending (equivalent a update).
     */
    public function delete($user, Order $order): bool
    {
        return $this->update($user, $order);
    }
}
