<?php

namespace App\Http\Controllers;

use App\Models\Botiga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BotigaController extends Controller
{
    /**
     * Retorna totes les botigues del venedor autenticat.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $botigues = Botiga::where('vendor_id', $user->id)->get();
        return response()->json($botigues);
    }

    /**
     * Guarda una nova botiga associada al venedor autenticat.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'descripcio' => 'nullable|string',
        ]);

        $botiga = Botiga::create([
            'vendor_id' => $user->id,
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
        ]);

        return response()->json($botiga, 201);
    }

    /**
     * Elimina una botiga només si pertany al venedor autenticat.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $botiga = Botiga::where('vendor_id', $user->id)->where('id', $id)->first();

        if (!$botiga) {
            return response()->json(['message' => 'Botiga no trobada'], 404);
        }

        $botiga->delete();

        return response()->json(['message' => 'Botiga eliminada correctament']);
    }
}
