<?php

namespace App\Http\Controllers;

use App\Models\Producte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProducteController extends Controller
{
    /**
     * Retorna tots els productes del venedor autenticat.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $productes = Producte::where('vendor_id', $user->id)->with('botigues')->get();
        return response()->json($productes);
    }

    /**
     * Guarda un nou producte associat al venedor autenticat.
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
            'preu' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'botiga_id' => 'required|exists:botigues,id', // 🔹 Comprova que la botiga existeix
        ]);
    
        $producte = Producte::create([
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
            'preu' => $request->preu,
            'stock' => $request->stock,
            'vendor_id' => $user->id,
        ]);
    
        // 🔹 Assigna el producte a la botiga
        $producte->botigues()->attach($request->botiga_id);
    
        return response()->json($producte, 201);
    }
    

    /**
     * Actualitza un producte només si pertany al venedor autenticat.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $producte = Producte::where('vendor_id', $user->id)->where('id', $id)->first();

        if (!$producte) {
            return response()->json(['message' => 'Producte no trobat'], 404);
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'descripcio' => 'nullable|string',
            'preu' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'imatge' => 'nullable|string',
        ]);

        $producte->update($request->only(['nom', 'descripcio', 'preu', 'stock', 'imatge']));

        return response()->json(['message' => 'Producte actualitzat amb èxit', 'producte' => $producte], 200);
    }

    /**
     * Elimina un producte només si pertany al venedor autenticat.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $producte = Producte::where('vendor_id', $user->id)->where('id', $id)->first();

        if (!$producte) {
            return response()->json(['message' => 'Producte no trobat'], 404);
        }

        $producte->delete();

        return response()->json(['message' => 'Producte eliminat correctament']);
    }
}
