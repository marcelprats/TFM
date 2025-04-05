<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Reserve;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Emmagatzema una nova comanda.
     */
    public function store(Request $request)
    {
        // Validació de les dades rebudes
        $validated = $request->validate([
            'reserve_id' => 'required|exists:reserves,id',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,paid,cancelled'
        ]);

        // Opcional: comprovar que la reserva pertany a l'usuari autenticat
        $reserve = Reserve::findOrFail($validated['reserve_id']);
        if ($reserve->buyer_id !== Auth::id()) {
            return response()->json(['message' => 'Accés no autoritzat.'], 403);
        }

        // Creem la comanda
        $order = Order::create([
            'reserve_id' => $validated['reserve_id'],
            'order_number' => 'ORD-' . time(), // Un codi únic per la comanda
            'total' => $validated['total_amount'],
            'payment_status' => $validated['status'],
            'payment_date' => now(),
        ]);

        // Retornem la resposta
        return response()->json([
            'message' => 'Comanda creada amb èxit.',
            'order' => $order,
        ], 201);
    }
}
