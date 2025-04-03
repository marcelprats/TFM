<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserve;

class ReserveController extends Controller
{
    // Mostra la llista de reserves per a l'usuari logejat (client)
    public function index(Request $request)
    {
        $userId = auth()->id();
        $reserves = Reserve::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->with('reserveItems')
            ->get();
        return response()->json($reserves);
    }

    // Mostra el detall d'una reserva concreta (amb els seus ítems)
    public function show($id)
    {
        $userId = auth()->id();
        $reserve = Reserve::where('user_id', $userId)
            ->with('reserveItems.product')
            ->findOrFail($id);
        return response()->json($reserve);
    }

    // Crea una nova reserva
    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'botiga_id' => 'required|exists:botigues,id',
            'total_price' => 'required|numeric',
            'reservation_fee' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'status' => 'required|string',
        ]);

        $data['user_id'] = auth()->id();
        $reserve = Reserve::create($data);
        return response()->json($reserve, 201);
    }

    // Actualitza una reserva existent (per exemple, actualitzar el pagament)
    public function update(Request $request, $id)
    {
        $reserve = Reserve::findOrFail($id);
        if ($reserve->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'total_price' => 'sometimes|numeric',
            'reservation_fee' => 'sometimes|numeric',
            'paid_amount' => 'sometimes|numeric',
            'status' => 'sometimes|string',
        ]);

        $reserve->update($data);
        return response()->json($reserve);
    }

    // Elimina una reserva (si és permesa)
    public function destroy($id)
    {
        $reserve = Reserve::findOrFail($id);
        if ($reserve->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $reserve->delete();
        return response()->json(['message' => 'Reserva eliminada']);
    }
}
