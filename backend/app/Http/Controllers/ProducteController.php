<?php

namespace App\Http\Controllers;

use App\Models\Producte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProducteController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $productes = Producte::where('vendor_id', $user->id)
            ->with('botiga:id,nom')
            ->get();

        return response()->json($productes);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $request->validate([
            'nom'         => 'required|string|max:255',
            'descripcio'  => 'nullable|string',
            'categoria'   => 'required|string',
            'subcategoria'=> 'nullable|string',
            'preu'        => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'botiga_id'   => 'required|exists:botigues,id',
            'imatge'      => 'nullable|file|image',
        ]);

        // Processar la imatge: si s'ha pujat un fitxer, el desa; sinó, es fa servir la URL enviada
        if ($request->hasFile('imatge')) {
            $file = $request->file('imatge');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imatgePath = '/uploads/' . $filename;
        } else {
            $imatgePath = $request->imatge;
        }

        $producte = Producte::create([
            'nom'         => $request->nom,
            'descripcio'  => $request->descripcio,
            'categoria'   => $request->categoria,
            'subcategoria'=> $request->subcategoria,
            'preu'        => $request->preu,
            'stock'       => $request->stock,
            'imatge'      => $imatgePath,
            'vendor_id'   => $user->id,
            'botiga_id'   => $request->botiga_id,
        ]);

        return response()->json($producte->load('botiga:id,nom'), 201);
    }

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
        
        $request->merge([
            'subcategoria' => $request->subcategoria ?: null,
        ]);

        $request->validate([
            'nom'         => 'required|string|max:255',
            'descripcio'  => 'nullable|string',
            'categoria'   => 'required|integer',
            'subcategoria'=> 'nullable|integer',
            'preu'        => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'botiga_id'   => 'required|exists:botigues,id',
            'imatge'      => 'nullable|file|image',
        ]);

        if ($request->hasFile('imatge')) {
            $file = $request->file('imatge');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $imatgePath = '/uploads/' . $filename;
        } else {
            $imatgePath = $request->imatge;
        }

        $producte->update([
            'nom'         => $request->nom,
            'descripcio'  => $request->descripcio,
            'categoria'   => $request->categoria,
            'subcategoria'=> $request->subcategoria,
            'preu'        => $request->preu,
            'stock'       => $request->stock,
            'imatge'      => $imatgePath,
            'botiga_id'   => $request->botiga_id,
        ]);

        return response()->json($producte->load('botiga:id,nom'));
    }

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

    public function getAllProducts()
    {
        $productes = Producte::with(['botiga:id,nom', 'vendor:id,name'])->get();

        return response()->json($productes);
    }

    public function show($id)
    {
        $producte = Producte::with(['botiga', 'vendor'])->find($id);

        if (!$producte) {
            return response()->json(['message' => 'Producte no trobat'], 404);
        }

        return response()->json($producte);
    }
}
