<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\{AddCartItemRequest, UpdateCartItemRequest, CheckoutCartRequest};
use App\Models\{Cart, CartItem, Producte, Order, Reserve, ReserveItem};
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Mostra el carret de l'usuari autenticat.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $this->authorize('viewAny', Cart::class);

        $morphType = array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
        $cart = Cart::firstOrCreate(
            ['owner_id' => $user->id, 'owner_type' => $morphType],
            ['total_price' => 0.00]
        );
        $cart->load('cartItems.product.botiga');

        return response()->json([
            'id'          => $cart->id,
            'total_price' => $cart->total_price,
            'cart_items'  => $cart->cartItems->map(fn($item) => [
                'id'             => $item->id,
                'product_id'     => $item->product_id,
                'quantity'       => $item->quantity,
                'reserved_price' => $item->reserved_price,
                'selected'       => $item->selected,
                'product'        => [
                    'id'    => $item->product->id,
                    'nom'   => $item->product->nom,
                    'preu'  => $item->product->preu,
                    'stock' => $item->product->stock,
                    'botiga'=> optional($item->product->botiga)->only(['id','nom']),
                ],
            ]),
        ]);
    }

    /**
     * Afegeix un ítem al carret, verificant l'estoc conjuntament amb existents.
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $user = auth()->user();
        $this->authorize('addItem', Cart::class);
        $data    = $request->validated();
        $product = Producte::findOrFail($data['product_id']);
        $stock   = $product->stock;

        $morphType = array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
        $cart      = Cart::firstOrCreate(
            ['owner_id' => $user->id, 'owner_type' => $morphType],
            ['total_price' => 0.00]
        );

        // Quantitat ja existent
        $existingQty = $cart->cartItems()
                            ->where('product_id', $data['product_id'])
                            ->value('quantity') ?: 0;

        if ($existingQty + $data['quantity'] > $stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Nom\u00e9s hi ha {$stock} unitats disponibles. Ja n'hi ha {$existingQty} al carro."],
            ]);
        }

        if ($existingQty) {
            $cart->cartItems()
                 ->where('product_id', $data['product_id'])
                 ->increment('quantity', $data['quantity']);
        } else {
            $cart->cartItems()->create([
                'product_id'     => $data['product_id'],
                'quantity'       => $data['quantity'],
                'reserved_price' => $product->preu,
                'selected'       => $data['selected'] ?? true,
            ]);
        }

        // Recalcular total
        $cart->update(['total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price)]);

        return response()->json($cart, 201);
    }

    /**
     * Actualitza un ítem del carret (quantitat / selected), amb control d'estoc.
     */
    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cartItem->load('cart', 'product');
        $this->authorize('update', $cartItem);

        $data  = $request->validated();
        $stock = $cartItem->product->stock;

        if ($data['quantity'] > $stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Nom\u00e9s hi ha {$stock} unitats disponibles."],
            ]);
        }

        $cartItem->update([
            'quantity' => $data['quantity'],
            'selected' => $data['selected'] ?? $cartItem->selected,
        ]);

        $cart = $cartItem->cart;
        $cart->update(['total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price)]);

        return response()->json($cart);
    }

    /**
     * Elimina un ítem.
     */
    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $this->authorize('delete', $cartItem);
        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->update(['total_price' => $cart->cartItems->sum(fn($i) => $i->quantity * $i->reserved_price)]);

        return response()->json($cart);
    }

    /**
     * Buidar tot el carret.
     */
    public function destroy(CartItem $cartItem): JsonResponse
    {
        $this->authorize('deleteAny', Cart::class);
        CartItem::where('cart_id', $cartItem->cart_id)->delete();

        return response()->json(['message' => 'Carro buidat correctament']);
    }

    /**
     * Checkout: crea reserves i ordres, decrementa stock.
     */
public function checkout(CheckoutCartRequest $request): JsonResponse
{
    $user      = auth()->user();
    $morphType = array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
    $cart      = Cart::where([
                        'owner_id'   => $user->id,
                        'owner_type' => $morphType,
                    ])
                    ->with('cartItems.product.botiga')
                    ->firstOrFail();

    $this->authorize('checkout', $cart);

    // 1) Agafem només els ítems marcats com a selected
    $selected = $cart->cartItems->where('selected', true);
    if ($selected->isEmpty()) {
        return response()->json(['message' => 'No hi ha ítems seleccionats'], 400);
    }

    // 2) Comprovació d’estoc: recollim tots els que fallen
    $outOfStock = [];
    foreach ($selected as $item) {
        $available = $item->product->stock;
        if ($item->quantity > $available) {
            $outOfStock[] = [
                'productId'   => $item->product->id,
                'productName' => $item->product->nom,
                'requested'   => $item->quantity,
                'available'   => $available,
            ];
        }
    }
    if (!empty($outOfStock)) {
        // Retornem 409 Conflict amb detall dels errors
        return response()->json([
            'message'    => 'Stock insuficient per a algun producte',
            'outOfStock' => $outOfStock,
        ], 409);
    }

    // 3) Càlcul del número base ORD-0000000XXX
    $lastOrder  = Order::latest('id')->first();
    $baseNumber = ($lastOrder && preg_match('/^ORD-(\d{10})/', $lastOrder->order_number, $m))
                ? intval($m[1]) + 1
                : 1;
    $baseOrderNo = 'ORD-' . str_pad($baseNumber, 10, '0', STR_PAD_LEFT);

    $orders = [];
    $i = 1;

    // 4) Creació de reserves i ordres per cada botiga
    foreach ($selected->groupBy(fn($item) => $item->product->botiga?->id ?? 'sense_botiga') as $botigaId => $items) {
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
            // Descomptem l'estoc aquí
            $item->product->decrement('stock', $item->quantity);

            ReserveItem::create([
                'reserve_id'     => $reserve->id,
                'product_id'     => $item->product_id,
                'quantity'       => $item->quantity,
                'reserved_price' => $item->reserved_price,
            ]);
        }

        $orderNumber = count($selected->groupBy(fn($i) => $i->product->botiga?->id ?? 'sense_botiga')) === 1
                       ? $baseOrderNo
                       : $baseOrderNo . '-' . $i;

        $order = Order::create([
            'reserve_id'     => $reserve->id,
            'order_number'   => $orderNumber,
            'total_amount'   => $total,
            'payment_method' => 'online',
            'status'         => 'pending',
            'buyer_id'       => $user->id,
            'buyer_type'     => $morphType,
            'payment_date'   => now(),
        ]);

        $orders[] = $order;
        $i++;
    }

    // 5) Neteja del carret i resposta
    $cart->cartItems()->where('selected', true)->delete();
    $cart->update(['total_price' => 0]);
    $cart->cartItems()->update(['selected' => true]);

    $orderIds = array_map(fn($o) => $o->id, $orders);

    return response()->json([
        'message'         => 'Checkout realitzat correctament.',
        'baseOrderNumber' => $baseOrderNo,
        'orderIds'        => $orderIds,
    ]);
}

/**
 * Comprova que tots els ítems seleccionats tinguin stock suficient.
 */
public function checkStock(): JsonResponse
{
    $user      = auth()->user();
    $morphType = array_search(get_class($user), Relation::morphMap()) ?: get_class($user);
    $cart      = Cart::where([
                        'owner_id'   => $user->id,
                        'owner_type' => $morphType,
                    ])
                    ->with('cartItems.product')
                    ->firstOrFail();

    // Només els seleccionats
    $selected = $cart->cartItems->where('selected', true);
    $outOfStock = [];

    foreach ($selected as $item) {
        $available = $item->product->stock;
        if ($item->quantity > $available) {
            $outOfStock[] = [
                'productId'   => $item->product->id,
                'productName' => $item->product->nom,
                'requested'   => $item->quantity,
                'available'   => $available,
            ];
        }
    }

    if (!empty($outOfStock)) {
        return response()->json([
            'message'    => 'Stock insuficient per a algun producte',
            'outOfStock' => $outOfStock,
        ], 409);
    }

    return response()->json(['success' => true]);
}


}
