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
     * Retorna el carret de l'usuari logejat, creant-lo si no existeix.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        Log::debug("Fetching cart for user id: {$userId}");
        
        // Obtenim el carret amb els ítems i els productes associats
        $cart = Cart::with('cartItems.product.botiga')->firstOrCreate(
            ['user_id' => $userId],
            ['total_price' => 0.00]
        );

        Log::debug("Cart found: " . json_encode($cart));

        return response()->json($cart);
    }

    /**
     * Afegeix un producte al carret.
     *
     * Requereix:
     * - product_id: ID del producte a afegir.
     * - quantity: Quantitat a afegir (mínim 1).
     */
    public function addItem(Request $request)
    {
        $userId = auth()->id();
        $validatedData = $request->validate([
            'product_id' => 'required|exists:productes,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        // Obtenim el producte per obtenir el seu preu actual.
        $product = Producte::findOrFail($validatedData['product_id']);
        $price = $product->preu;

        // Obtenim o creem el carret de l'usuari.
        $cart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['total_price' => 0.00]
        );

        // Comprovem si l'ítem ja existeix al carret.
        $cartItem = $cart->cartItems()->where('product_id', $validatedData['product_id'])->first();

        if ($cartItem) {
            // Incrementem la quantitat si l'ítem ja existeix.
            $cartItem->quantity += $validatedData['quantity'];
            $cartItem->save();
        } else {
            // Creem un nou ítem al carret.
            $cartItem = $cart->cartItems()->create([
                'product_id'    => $validatedData['product_id'],
                'quantity'      => $validatedData['quantity'],
                'reserved_price' => $price,
            ]);
        }

        // Actualitzem el total del carret.
        $cart->total_price = $cart->cartItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->save();

        Log::debug("Product added to cart", ['cart' => $cart]);

        return response()->json($cart);
    }

    /**
     * Actualitza la quantitat d'un ítem del carret.
     *
     * Requereix:
     * - quantity: La nova quantitat.
     */
    public function updateItem(Request $request, $itemId)
    {
        $userId = auth()->id();
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
            'selected' => 'sometimes|boolean',
        ]);

        $cartItem = CartItem::findOrFail($itemId);

        // Comprovem que l'ítem pertany al carret de l'usuari.
        if ($cartItem->cart->user_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->quantity = $validatedData['quantity'];
        if (array_key_exists('selected', $validatedData)) {
            $cartItem->selected = $validatedData['selected'];
        }
        $cartItem->save();

        // Actualitzem el total del carret.
        $cart = $cartItem->cart;
        $cart->total_price = $cart->cartItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->save();

        return response()->json($cart);
    }

    /**
     * Elimina un ítem del carret.
     */
    public function removeItem(Request $request, $itemId)
    {
        $userId = auth()->id();
        $cartItem = CartItem::findOrFail($itemId);

        if ($cartItem->cart->user_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        // Actualitzem el total del carret després de l'eliminació.
        $cart->total_price = $cart->cartItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->save();

        return response()->json($cart);
    }

    /**
     * Buidar o eliminar el carret de l'usuari.
     *
     * Aquest mètode s'executa quan es vol buidar el carret després del checkout.
     */
    public function destroy(Request $request)
    {
        $userId = auth()->id();
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart) {
            // Esborrem tots els ítems del carret
            $cart->cartItems()->delete();
            // Opcional: posem el total a 0
            $cart->total_price = 0;
            $cart->save();
        }
        
        return response()->json(['message' => 'Carret buidat correctament.'], 200);
    }

    /**
     * Simula el checkout del carret.
     *
     * En una implementació real, aquí es crearia una comanda i es processaria el pagament.
     */
    public function checkout(Request $request)
    {
        $userId = auth()->id();
    
        // Carrega el carret amb la relació product.botiga
        $cart = Cart::with('cartItems.product.botiga')->where('user_id', $userId)->first();
    
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
    
        // Agrupa els ítems seleccionats per botiga; si no hi ha botiga, s'assigna 'sense_botiga'
        $groups = $selectedItems->groupBy(function ($item) {
            return $item->product->botiga ? $item->product->botiga->id : 'sense_botiga';
        });
    
        // Obté l'últim número d'ordre base
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
            // Calcula el total reservat per aquest grup
            $total = $items->sum(function ($item) {
                return $item->quantity * $item->reserved_price;
            });
            $deposit = round($total * 0.1, 2);
    
            // Defineix el número d'ordre:
            // Si només hi ha un grup, s'utilitza el número base; sinó, s'afegeix el sufix "-n"
            $orderNumber = count($groups) === 1
                ? $baseOrderNumber
                : $baseOrderNumber . '-' . $groupIndex;
    
            // Crea la reserva per aquest grup
            $reserve = \App\Models\Reserve::create([
                'buyer_id'       => $userId,
                'botiga_id'      => ($shopId === 'sense_botiga') ? null : $shopId,
                'total_reserved' => $total,
                'deposit_amount' => $deposit,
                'status'         => 'pending',
            ]);
    
            // Per cada ítem del grup:
            // 1. Crea el registre a la taula reserve_items.
            // 2. Resta la quantitat reservada del stock del producte.
            foreach ($items as $item) {
                \App\Models\ReserveItem::create([
                    'reserve_id'     => $reserve->id,
                    'product_id'     => $item->product->id,
                    'quantity'       => $item->quantity,
                    'reserved_price' => $item->reserved_price,
                ]);
    
                // Actualitza el stock del producte
                $product = $item->product;
                $newStock = $product->stock - $item->quantity;
                // Si el nou stock és negatiu, es fixa a 0
                if ($newStock < 0) {
                    $newStock = 0;
                }
                $product->update(['stock' => $newStock]);
            }
    
            // Crea la comanda (ordre) per aquesta reserva
            $order = \App\Models\Order::create([
                'reserve_id'     => $reserve->id,
                'order_number'   => $orderNumber,
                'total_amount'   => $total,
                'payment_method' => 'online',  // Ajusta segons la teva lògica
                'transaction_id' => null,
                'status'         => 'pending',
                'buyer_id'       => $userId,
                'payment_date'   => now(),
            ]);
    
            $orders[] = $order;
            $groupIndex++;
        }
    
        // Elimina només els ítems processats (selected == true)
        $cart->cartItems()->where('selected', true)->delete();
    
        // Recalcula el total del carret amb els ítems restants
        $remainingItems = $cart->cartItems;
        $remainingTotal = $remainingItems->sum(function ($item) {
            return $item->quantity * $item->reserved_price;
        });
        $cart->total_price = $remainingTotal;
        $cart->save();
    
        // Marca els ítems restants com a seleccionats per defecte
        $cart->cartItems()->update(['selected' => true]);
    
        // Retorna la resposta.
        if (count($orders) === 1) {
            return response()->json([
                'message' => 'Checkout realitzat correctament.',
                'orderId' => $orders[0]->id,
                'baseOrderNumber' => $baseOrderNumber
            ]);
        } else {
            return response()->json([
                'message' => 'Checkout realitzat correctament. S\'han creat diverses ordres per botiga.',
                'orders'  => $orders,
                'baseOrderNumber' => $baseOrderNumber
            ]);
        }
    }
    
}
