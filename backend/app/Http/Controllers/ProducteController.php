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

        $productes = Producte::where('vendor_id', $user->id)
        ->with(['botigues' => function ($query) {
            $query->select('botigues.id', 'botigues.nom');
        }])
        ->get();
    

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
            'botiga_id' => 'required|exists:botigues,id', 
        ]);
    
        $producte = Producte::create([
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
            'preu' => $request->preu,
            'stock' => $request->stock,
            'vendor_id' => $user->id,
        ]);
    
        if ($request->botiga_id) {
            $producte->botigues()->sync([$request->botiga_id]);
        }
    
        // 🔹 Carregar les botigues correctament en la resposta
        return response()->json($producte->load('botigues:id,nom'), 201);
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
        ]);
    
        $producte->update($request->only(['nom', 'descripcio', 'preu', 'stock']));
    
        if ($request->botiga_id) {
            $producte->botigues()->sync([$request->botiga_id]);
        }
    
        return response()->json($producte->load('botigues:id,nom'));
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

    /**
     * Retorna tots els productes disponibles amb la informació de les botigues i venedors.
     */
    public function getAllProducts()
    {
        $productes = Producte::with(['botigues:id,nom', 'vendor:id,name'])->get();

        return response()->json($productes);
    }

    public function show($id)
    {
        $producte = Producte::with(['botigues', 'vendor'])->find($id);

        if (!$producte) {
            return response()->json(['message' => 'Producte no trobat'], 404);
        }

        return response()->json($producte);
    }


}



