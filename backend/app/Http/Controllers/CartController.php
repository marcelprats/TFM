<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Producte;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Retorna el carret del propietari autenticat, creant-lo si no existeix.
     */
    public function index(Request $request)
    {
        // Obtenim l'usuari autenticat directament des del request
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $ownerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        Log::debug("Fetching cart for owner id: {$ownerId} and type: {$ownerType}");

        // Recuperem o creem el carret amb la relació polimòrfica
        $cart = Cart::with('cartItems.product.botiga')->firstOrCreate(
            ['owner_id' => $ownerId, 'owner_type' => $ownerType],
            ['total_price' => 0.00]
        );

        Log::debug("Cart found: " . json_encode($cart));
        return response()->json($cart);
    }

    /**
     * Afegeix un producte al carret.
     * Requereix:
     * - product_id: ID del producte a afegir.
     * - quantity: Quantitat a afegir (mínim 1).
     */
    public function addItem(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $ownerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        $validatedData = $request->validate([
            'product_id' => 'required|exists:productes,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        // Obtenim el producte per extreure el seu preu actual
        $product = Producte::findOrFail($validatedData['product_id']);
        $price = $product->preu;

        // Recuperem o creem el carret del propietari
        $cart = Cart::firstOrCreate(
            ['owner_id' => $ownerId, 'owner_type' => $ownerType],
            ['total_price' => 0.00]
        );

        // Comprovem si l'ítem ja existeix al carret
        $cartItem = $cart->cartItems()->where('product_id', $validatedData['product_id'])->first();
        if ($cartItem) {
            // Incrementem la quantitat de l'ítem existent
            $cartItem->quantity += $validatedData['quantity'];
            $cartItem->save();
        } else {
            // Creem un nou ítem al carret
            $cartItem = $cart->cartItems()->create([
                'product_id'     => $validatedData['product_id'],
                'quantity'       => $validatedData['quantity'],
                'reserved_price' => $price,
            ]);
        }

        // Recalculem el total del carret
        $cart->total_price = $cart->cartItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->save();

        Log::debug("Product added to cart", ['cart' => $cart]);

        return response()->json($cart);
    }

    /**
     * Actualitza la quantitat d'un ítem del carret.
     * Requereix:
     * - quantity: La nova quantitat.
     */
    public function updateItem(Request $request, $itemId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $ownerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
            'selected' => 'sometimes|boolean',
        ]);

        $cartItem = CartItem::findOrFail($itemId);

        // Comprovem que l'ítem pertany al carret del propietari autenticat
        if ($cartItem->cart->owner_id != $ownerId || $cartItem->cart->owner_type != $ownerType) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->quantity = $validatedData['quantity'];
        if (array_key_exists('selected', $validatedData)) {
            $cartItem->selected = $validatedData['selected'];
        }
        $cartItem->save();

        // Recalculem el total del carret
        $cart = $cartItem->cart;
        $cart->total_price = $cart->cartItems->sum(fn($item) => $item->quantity * $item->reserved_price);
        $cart->save();

        return response()->json($cart);
    }

    /**
     * Elimina un ítem del carret.
     */
    public function removeItem(Request $request, $itemId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $ownerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        $cartItem = CartItem::findOrFail($itemId);
        if ($cartItem->cart->owner_id != $ownerId || $cartItem->cart->owner_type != $ownerType) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        // Actualitzem el total del carret
        $cart->total_price = $cart->cartItems->sum(fn($item) => $item->quantity * $item->reserved_price);
        $cart->save();

        return response()->json($cart);
    }

    /**
     * Buida el carret del propietari.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $ownerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        $cart = Cart::where('owner_id', $ownerId)
            ->where('owner_type', $ownerType)
            ->first();

        if ($cart) {
            $cart->cartItems()->delete();
            $cart->total_price = 0;
            $cart->save();
        }
        
        return response()->json(['message' => 'Carret buidat correctament.'], 200);
    }

    /**
     * Processa el checkout del carret.
     *
     * En una implementació real, aquí es crearia una comanda i es processaria el pagament.
     */
    public function checkout(Request $request)
    {
        // Recupera l'usuari autenticat
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $ownerId = $user->id;
        $buyerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';
    
        // Carrega el carret amb les relacions
        $cart = Cart::with('cartItems.product.botiga')
            ->where('owner_id', $ownerId)
            ->where('owner_type', $buyerType)
            ->first();
    
        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'El carret està buit.'], 400);
        }
    
        // Filtra els ítems seleccionats (on selected == true)
        $selectedItems = $cart->cartItems->filter(function ($item) {
            return $item->selected;
        });
        if ($selectedItems->isEmpty()) {
            return response()->json(['message' => 'No hi ha ítems seleccionats per fer checkout.'], 400);
        }
    
        // Agrupa els ítems seleccionats per botiga (si no hi ha botiga, assigna 'sense_botiga')
        $groups = $selectedItems->groupBy(function ($item) {
            return $item->product->botiga ? $item->product->botiga->id : 'sense_botiga';
        });
    
        // Genera el número d'ordre base
        $lastOrder = \App\Models\Order::orderBy('id', 'desc')->first();
        if ($lastOrder && preg_match('/^ORD-(\d{10})/', $lastOrder->order_number, $matches)) {
            $baseNumber = intval($matches[1]) + 1;
        } else {
            $baseNumber = 1;
        }
        $baseOrderNumber = 'ORD-' . str_pad($baseNumber, 10, '0', STR_PAD_LEFT);
    
        $orders = [];
        $groupIndex = 1;
        foreach ($groups as $shopId => $items) {
            $total = $items->sum(function ($item) {
                return $item->quantity * $item->reserved_price;
            });
            $deposit = round($total * 0.1, 2);
            $orderNumber = (count($groups) === 1)
                ? $baseOrderNumber
                : $baseOrderNumber . '-' . $groupIndex;
    
            // Aquí creem la reserva. Ara s'inclou el camp 'buyer_type'
            $reserve = \App\Models\Reserve::create([
                'buyer_id'       => $ownerId,
                'buyer_type'     => $buyerType, // Afegim aquest camp
                'botiga_id'      => ($shopId === 'sense_botiga') ? null : $shopId,
                'total_reserved' => $total,
                'deposit_amount' => $deposit,
                'status'         => 'pending',
            ]);
    
            foreach ($items as $item) {
                \App\Models\ReserveItem::create([
                    'reserve_id'     => $reserve->id,
                    'product_id'     => $item->product->id,
                    'quantity'       => $item->quantity,
                    'reserved_price' => $item->reserved_price,
                ]);
    
                // Actualitza el stock del producte
                $product = $item->product;
                $newStock = max(0, $product->stock - $item->quantity);
                $product->update(['stock' => $newStock]);
            }
    
            $order = \App\Models\Order::create([
                'reserve_id'     => $reserve->id,
                'order_number'   => $orderNumber,
                'total_amount'   => $total,
                'payment_method' => 'online',
                'transaction_id' => null,
                'status'         => 'pending',
                'buyer_id'       => $ownerId,
                'buyer_type'     => $buyerType,
                'payment_date'   => now(),
            ]);
    
            $orders[] = $order;
            $groupIndex++;
        }
    
        // Esborra els ítems processats (selected == true)
        $cart->cartItems()->where('selected', true)->delete();
    
        // Recalcula el total amb els ítems restants
        $remainingTotal = $cart->cartItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->total_price = $remainingTotal;
        $cart->save();
    
        // Marca els ítems restants com a seleccionats per defecte
        $cart->cartItems()->update(['selected' => true]);
    
        if (count($orders) === 1) {
            return response()->json([
                'message' => 'Checkout realitzat correctament.',
                'orderId' => $orders[0]->id,
                'baseOrderNumber' => $baseOrderNumber,
            ]);
        } else {
            return response()->json([
                'message' => 'Checkout realitzat correctament. S\'han creat diverses ordres per botiga.',
                'orders' => $orders,
                'baseOrderNumber' => $baseOrderNumber,
            ]);
        }
    }
    
}
