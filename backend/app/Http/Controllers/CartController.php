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
        $cart = Cart::with('cartItems.product')->firstOrCreate(
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
        ]);

        $cartItem = CartItem::findOrFail($itemId);

        // Comprovem que l'ítem pertany al carret de l'usuari.
        if ($cartItem->cart->user_id != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->quantity = $validatedData['quantity'];
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
     * Simula el checkout del carret.
     *
     * En una implementació real, aquí es crearia una comanda i es processaria el pagament.
     */
    public function checkout(Request $request)
    {
        $userId = auth()->id();
        $cart = Cart::with('cartItems.product')->where('user_id', $userId)->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'El carret està buit.'], 400);
        }

        // Aquí podríem crear una comanda i processar el pagament.
        // Per ara, només retornem el carret com a confirmació.
        return response()->json([
            'message' => 'Checkout simulat correctament.',
            'cart' => $cart,
        ]);
    }
}
