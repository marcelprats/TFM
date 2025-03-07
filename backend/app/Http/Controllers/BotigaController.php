<?php

namespace App\Http\Controllers;

use App\Models\Botiga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BotigaController extends Controller
{
    /**
     * Retorna totes les botigues.
     */
    public function indexPublic()
    {
        return response()->json(Botiga::all());
    }

    /**
     * Retorna totes les botigues del venedor autenticat.
     */
    public function getBotiguesByAuthVendor()
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
    
        \Log::info("📥 Dades rebudes per crear botiga:", $request->all()); // 🔥 LOG
    
        $request->validate([
            'nom' => 'required|string|max:255',
            'descripcio' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
    
        $botiga = Botiga::create([
            'vendor_id' => $user->id,
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
    
        return response()->json($botiga, 201);
    }
    

    /**
     * Actualitza una botiga només si pertany al venedor autenticat.
     */
    public function update(Request $request, $id)
    {
        Log::info("📥 Dades rebudes en update():", $request->all());
    
        $user = Auth::user();
    
        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }
    
        $botiga = Botiga::where('vendor_id', $user->id)->where('id', $id)->first();
    
        if (!$botiga) {
            return response()->json(['message' => 'Botiga no trobada'], 404);
        }
    
        $request->validate([
            'nom' => 'required|string|max:255',
            'descripcio' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
    
        Log::info("🔄 Actualitzant botiga amb:", [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);
    
        $botiga->update([
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
    
        return response()->json(['message' => 'Botiga actualitzada amb èxit', 'botiga' => $botiga], 200);
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

    /**
     * Retorna una botiga per ID amb els seus productes associats
     */ 
    public function show($id)
    {
        $botiga = Botiga::with('productes')->find($id);
    
        if (!$botiga) {
            return response()->json(['error' => 'Botiga no trobada'], 404);
        }
    
        return response()->json($botiga);
    }
}
