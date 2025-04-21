<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\{AddCartItemRequest, UpdateCartItemRequest, CheckoutCartRequest};
use App\Models\{Cart, CartItem, Order, Producte, Reserve, ReserveItem};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\Relation;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $this->authorize('viewAny', Cart::class);
    
        $morphType = array_search(get_class($user), Relation::morphMap()) ?? get_class($user);
    
        $cart = Cart::where('owner_id', $user->id)
                    ->where('owner_type', $morphType)
                    ->first();
    
        if (!$cart) {
            $cart = $user->cart()->create([
                'total_price' => 0.00,
                'owner_type'  => $morphType, // ← important!
            ]);
        }
    
        $cart->load('cartItems.product.botiga');
    
        return response()->json([
            'id'           => $cart->id,
            'total_price'  => $cart->total_price,
            'cart_items'   => $cart->cartItems->map(function ($item) {
                return [
                    'id'             => $item->id,
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity,
                    'reserved_price' => $item->reserved_price,
                    'selected'       => $item->selected,
                    'product'        => [
                        'id'     => $item->product->id,
                        'nom'    => $item->product->nom,
                        'preu'   => $item->product->preu,
                        'botiga' => optional($item->product->botiga)->only(['id', 'nom']),
                    ],
                ];
            }),
        ]);
    }
    

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $user = auth()->user();
        $this->authorize('addItem', Cart::class);
        $data = $request->validated();
    
        $morphType = array_search(get_class($user), Relation::morphMap()) ?? get_class($user);
    
        $cart = Cart::where('owner_id', $user->id)
                    ->where('owner_type', $morphType)
                    ->first();
    
        if (!$cart) {
            $cart = $user->cart()->create([
                'total_price' => 0.00,
                'owner_type'  => $morphType, // ← assegura't que es guarda correctament
            ]);
        }
    
        $product = Producte::findOrFail($data['product_id']);
        $price   = $product->preu;
    
        $cartItem = $cart->cartItems()->where('product_id', $data['product_id'])->first();
        if ($cartItem) {
            $cartItem->increment('quantity', $data['quantity']);
        } else {
            $cart->cartItems()->create([
                'product_id'     => $data['product_id'],
                'quantity'       => $data['quantity'],
                'reserved_price' => $price,
            ]);
        }
    
        $cart->update([
            'total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price),
        ]);
    
        return response()->json($cart, 201);
    }
    

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        // Primer carrega el cart
        $cartItem->loadMissing('cart');
    
        // Ara ja pot passar la política
        $this->authorize('update', $cartItem);
    
        $data = $request->validated();
        $cartItem->update([
            'quantity' => $data['quantity'],
            'selected' => $data['selected'] ?? $cartItem->selected,
        ]);
    
        $cart = $cartItem->cart;
        $cart->update([
            'total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price),
        ]);
    
        return response()->json($cart);
    }
    
    
    

    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $cartItem->loadMissing('cart');
        $this->authorize('delete', $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();

        $cart->update([
            'total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price),
        ]);

        return response()->json($cart);
    }

    public function destroy(CartItem $cartItem)
    {
        $this->authorize('delete', $cartItem); // Aquesta línia és clau
    
        $cartItem->delete();
    
        return response()->json(['message' => 'Ítem eliminat correctament']);
    }
    
    

    public function checkout(CheckoutCartRequest $request): JsonResponse
    {
        $user = auth()->user();
        $cart = Cart::where('owner_id', $user->id)
                    ->where('owner_type', get_class($user))
                    ->with('cartItems.product.botiga')
                    ->firstOrFail();
    
        $this->authorize('checkout', $cart);
    
        $selectedItems = $cart->cartItems->where('selected', true);
        if ($selectedItems->isEmpty()) {
            return response()->json(['message' => 'No hi ha ítems seleccionats per fer checkout.'], 400);
        }
    
        $groups = $selectedItems->groupBy(fn($item) => $item->product->botiga?->id ?? 'sense_botiga');
    
        $lastOrder   = Order::latest('id')->first();
        $baseNumber  = ($lastOrder && preg_match('/^ORD-(\d{10})/', $lastOrder->order_number, $m)) ? intval($m[1]) + 1 : 1;
        $baseOrderNo = 'ORD-' . str_pad($baseNumber, 10, '0', STR_PAD_LEFT);
    
        $orders = [];
        $i = 1;
    
        $morphType = array_search(get_class($user), Relation::morphMap()) ?? get_class($user);
    
        foreach ($groups as $botigaId => $items) {
            $total   = $items->sum(fn($i) => $i->quantity * $i->reserved_price);
            $deposit = round($total * 0.1, 2);
    
            $reserve = Reserve::create([
                'buyer_id'       => $user->id,
                'buyer_type'     => $morphType,
                'botiga_id'      => $botigaId === 'sense_botiga' ? null : $botigaId,
                'total_reserved' => $total,
                'deposit_amount' => $deposit,
                'status'         => 'pending',
            ]);
    
            foreach ($items as $item) {
                ReserveItem::create([
                    'reserve_id'     => $reserve->id,
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity,
                    'reserved_price' => $item->reserved_price,
                ]);
                $item->product->decrement('stock', $item->quantity);
            }
    
            $order = Order::create([
                'reserve_id'     => $reserve->id,
                'order_number'   => count($groups) === 1 ? $baseOrderNo : $baseOrderNo . '-' . $i,
                'total_amount'   => $total,
                'payment_method' => 'online',
                'transaction_id' => null,
                'status'         => 'pending',
                'buyer_id'       => $user->id,
                'buyer_type'     => $morphType,
                'payment_date'   => now(),
            ]);
    
            $orders[] = $order;
            $i++;
        }
    
        $cart->cartItems()->where('selected', true)->delete();
        $cart->update([
            'total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price),
        ]);
        $cart->cartItems()->update(['selected' => true]);
    
        return response()->json([
            'message'         => 'Checkout realitzat correctament.',
            'orders'          => $orders,
            'baseOrderNumber' => $baseOrderNo,
        ]);
    }
    
}
