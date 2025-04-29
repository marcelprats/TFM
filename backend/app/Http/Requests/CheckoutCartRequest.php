<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Relations\Relation;

class CheckoutCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Agafem l'usuari
        $user = $this->user();

        // Construïm el morphType igual que al controller
        $morphType = array_search(get_class($user), Relation::morphMap()) ?: get_class($user);

        // Carreguem el Carrt
        $cart = Cart::where('owner_id', $user->id)
                    ->where('owner_type', $morphType)
                    ->first();

        // Deixem passar si hi ha un cart i l'usuari pot fer checkout segons la Policy
        return $cart
            && $user->can('checkout', $cart);
    }

    public function rules(): array
    {
        return [
            // Si no necessites cap input addicional, pots deixar-ho buit
        ];
    }
}
