<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Reserve;

class OrderController extends Controller
{
    /**
     * Emmagatzema una nova comanda.
     */
    public function store(Request $request)
    {
        // Validació de les dades rebudes
        $validated = $request->validate([
            'reserve_id'     => 'required|exists:reserves,id',
            'total_amount'   => 'required|numeric',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status'         => 'required|in:pending,paid,cancelled'
        ]);

        // Obtenim l'usuari autenticat usant $request->user()
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }

        // Comprovar que la reserva pertany a l'usuari
        $reserve = Reserve::findOrFail($validated['reserve_id']);
        if ($reserve->buyer_id !== $user->id) {
            return response()->json(['message' => 'Accés no autoritzat.'], 403);
        }

        // Determina el tipus de comprador basant-se en el model de l'usuari
        $buyerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        // Creem la comanda amb el buyer_type assignat
        $order = Order::create([
            'reserve_id'     => $validated['reserve_id'],
            'order_number'   => 'ORD-' . time(),  // Genera un número d'ordre únic
            'total_amount'   => $validated['total_amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'status'         => $validated['status'],
            'buyer_id'       => $user->id,
            'buyer_type'     => $buyerType,
            'payment_date'   => now(),
        ]);

        return response()->json([
            'message' => 'Comanda creada amb èxit.',
            'order'   => $order,
        ], 201);
    }

    /**
     * Mostra una comanda concreta.
     */
    public function show(Request $request, $id)
    {
        // Carrega l'ordre amb les relacions
        $order = Order::with([
            'reserve.reserveItems.product',
            'reserve.botiga'
        ])->findOrFail($id);
    
        // Obtenim l'usuari autenticat
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
    
        // Determina el buyerType segons el model de l'usuari
        $buyerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';
    
        // Comprova que l'ordre pertany al usuari autenticat
        if ($order->buyer_id !== $user->id || $order->buyer_type !== $buyerType) {
            return response()->json(['message' => 'Accés no autoritzat.'], 403);
        }
    
        return response()->json($order);
    }
    
    /**
     * Llista totes les comandes de l'usuari autenticat.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $buyerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';

        $orders = Order::with('reserve.reserveItems.product')
                    ->where('buyer_id', $user->id)
                    ->where('buyer_type', $buyerType)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($orders);
    }

    /**
     * Llista les comandes associades a la botiga del venedor autenticat.
     */
    public function vendorOrders(Request $request)
    {
        $user = $request->user();
        if (!$user || !($user instanceof \App\Models\Vendor)) {
            return response()->json(['message' => 'Accés no autoritzat.'], 403);
        }

        $orders = Order::with([
            'reserve.reserveItems.product',
            'reserve.botiga'
        ])
        ->whereHas('reserve.botiga', function($query) use ($user) {
            $query->where('vendor_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($orders);
    }

    /**
     * Actualitza l'estat d'una comanda.
     *
     * Paràmetres esperats:
     *  - status: 'reserved', 'completed' o 'cancelled' (obligatori)
     *  - cancellation_reason: opció per a cancel·lació
     *  - confirmed_product_ids: opcional, array d'IDs dels ítems confirmats
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status'                => 'required|in:reserved,completed,cancelled',
            'cancellation_reason'   => 'nullable|string',
            'confirmed_product_ids' => 'nullable|array'
        ]);

        $order = Order::with('reserve')->findOrFail($id);
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }

        // Comprova que l'ordre pertany a la botiga del venedor autenticat
        if (!$order->reserve || !$order->reserve->botiga || $order->reserve->botiga->vendor_id != $user->id) {
            return response()->json(['message' => 'Accés no autoritzat.'], 403);
        }

        $order->status = $validated['status'];
        if ($validated['status'] === 'cancelled' && isset($validated['cancellation_reason'])) {
            $order->cancellation_reason = $validated['cancellation_reason'];
        }
        if ($validated['status'] === 'reserved' && isset($validated['confirmed_product_ids'])) {
            \Log::info('Productes confirmats per l’ordre ' . $order->id . ': ' . implode(',', $validated['confirmed_product_ids']));
            // Aquí es podria actualitzar la confirmació d'ítems, si s'ho desitja.
        }
        $order->save();

        return response()->json($order);
    }
}
