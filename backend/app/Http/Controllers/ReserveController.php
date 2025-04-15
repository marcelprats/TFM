<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Reserve;

class ReserveController extends Controller
{
    // Mostra la llista de reserves per a l'usuari logejat (client)
    public function index(Request $request)
    {
        $userId = auth()->id();
        $reserves = Reserve::where('buyer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->with('reserveItems')
            ->get();
        return response()->json($reserves);
    }

    // Mostra el detall d'una reserva concreta (amb els seus ítems)
    public function show($id)
    {
        $userId = auth()->id();
        $reserve = Reserve::where('buyer_id', $userId)
            ->with('reserveItems.product')
            ->findOrFail($id);
        return response()->json($reserve);
    }

    // Crea una nova reserva
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Usuari no autenticat.'], 401);
        }
        $buyerType = ($user instanceof \App\Models\Vendor) ? 'vendor' : 'user';
    
        $data = $request->validate([
            'botiga_id'       => 'required|exists:botigues,id',
            'total_price'     => 'required|numeric',
            'reservation_fee' => 'required|numeric',
            'status'          => 'required|string',
        ]);
    
        $dataToInsert = [
            'buyer_id'       => $user->id,
            'buyer_type'     => $buyerType,  // Inclou buyer_type
            'botiga_id'      => $data['botiga_id'],
            'total_reserved' => $data['total_price'],
            'deposit_amount' => $data['reservation_fee'],
            'status'         => $data['status'],
        ];
    
        $reserve = Reserve::create($dataToInsert);
    
        // Resta de la lògica per transferir els ítems del carret, etc.
        // ...
    
        return response()->json($reserve, 201);
    }
    
    


    // Actualitza una reserva existent (per exemple, actualitzar el pagament)
    public function update(Request $request, $id)
    {
        $reserve = Reserve::findOrFail($id);
        if ($reserve->buyer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'vendor_id'      => 'required|exists:vendors,id',
            'botiga_id'      => 'required|exists:botigues,id',
            'total_price'    => 'required|numeric',
            'reservation_fee'=> 'required|numeric',
            'paid_amount'    => 'nullable|numeric',
            'status'         => 'required|string',
        ]);
        
        $dataToInsert = [
            'buyer_id'       => auth()->id(),
            'botiga_id'      => $data['botiga_id'],
            'total_reserved' => $data['total_price'],
            'deposit_amount' => $data['reservation_fee'],
            'status'         => $data['status'],
        ];
        
        $reserve->update($dataToInsert);
        
        return response()->json($reserve);
    }

    // Elimina una reserva (si és permesa)
    public function destroy($id)
    {
        $reserve = Reserve::findOrFail($id);
        if ($reserve->buyer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $reserve->delete();
        return response()->json(['message' => 'Reserva eliminada']);
    }
}
