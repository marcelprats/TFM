<?php

namespace App\Http\Controllers;

use App\Models\Producte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProducteController extends Controller
{
    /**
     * Retorna la llista de productes del venedor autenticat.
     * (Endpoints per ús de venedors)
     */
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

    /**
     * Crea un nou producte associat al venedor autenticat.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        // Valida les dades rebudes
        $request->validate([
            'nom'         => 'required|string|max:255',
            'descripcio'  => 'nullable|string',
            // Decideix un tipus coherent per a categoria i subcategoria (en aquest cas, string)
            'categoria'   => 'required|string',
            'subcategoria'=> 'nullable|string',
            'preu'        => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'botiga_id'   => 'required|exists:botigues,id',
            'imatge'      => 'nullable|file|image',
        ]);

        // Processa la imatge: si s'ha pujat un fitxer, el desa; en cas contrari, s'utilitza la URL enviada
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

    /**
     * Actualitza un producte si pertany al venedor autenticat.
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

        // Si la subcategoria no es proporciona, la defineix com a null
        $request->merge([
            'subcategoria' => $request->subcategoria ?: null,
        ]);

        // Validació: assegura't que els tipus per a categoria i subcategoria siguin coherents (string)
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

        // Processa la imatge, si s'ha pujat
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

    /**
     * Actualitza el stock d’un producte.
     */
    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'stock' => 'required|numeric|min:0',
        ]);

        $producte = Producte::findOrFail($id);
        $producte->stock = $validated['stock'];
        $producte->save();

        return response()->json([
            'message' => 'Stock actualitzat correctament.',
            'producte' => $producte
        ]);
    }

    /**
     * Elimina un producte si pertany al venedor autenticat.
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
     * Retorna tots els productes públics amb informació bàsica.
     * (Endpoint per a consultes públiques)
     */
    public function getAllProducts()
    {
        $productes = Producte::with(['botiga:id,nom', 'vendor:id,name'])->get();

        return response()->json($productes);
    }

    /**
     * Retorna els detalls d’un producte per ID amb les seves relacions.
     * (Endpoint públic)
     */
    public function show($id)
    {
        $producte = Producte::with(['botiga', 'vendor'])->find($id);

        if (!$producte) {
            return response()->json(['message' => 'Producte no trobat'], 404);
        }

        return response()->json($producte);
    }
}
