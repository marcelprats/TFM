<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    /**
     * Retorna la llista pública de venedors.
     * Aquesta ruta no hauria d'estar protegida per middleware d’autenticació.
     */
    public function indexPublic()
    {
        // Carrega els venedors amb la relació 'botigues'
        $vendors = Vendor::with('botigues')->get();
        return response()->json($vendors);
    }

    /**
     * Retorna la llista completa de venedors.
     * Aquest mètode està pensat per a rutes protegides (per exemple, dins d’un grup 'auth:vendor')
     * i pots afegir restriccions o informació addicional segons el perfil del venedor autenticat.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || $user->getTable() !== 'vendors') {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        // En un context protegit, pots donar més informació o restringir certes dades.
        $vendors = Vendor::with('botigues')->get();
        return response()->json($vendors);
    }

    /**
     * Retorna la informació d'un venedor per ID.
     * Aquesta ruta pot ser pública o protegida segons les teves necessitats.
     */
    public function show($id)
    {
        $vendor = Vendor::with('botigues')->find($id);
        if (!$vendor) {
            return response()->json(['error' => 'Venedor no trobat'], 404);
        }
        return response()->json($vendor);
    }

    public function showPublic($id)
    {
        $vendor = Vendor::with(['botigues' => function ($q) {
            $q->with('productes');
        }])->findOrFail($id);
    
        return response()->json($vendor);
    }
    


    /**
     * Actualitza les dades d'un venedor.
     * Només accessible pel venedor mateix (o per un administrador en cas d’haver-ne).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        // Comprovem que l'usuari està autenticat, és un venedor i que és el mateix que volem actualitzar
        if (!$user || $user->getTable() !== 'vendors' || $user->id != $id) {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        // Valida les dades; utilitza "name" perquè és el camp al model Vendor
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|unique:vendors,email,' . $id,
            // Pots afegir més camps si n'hi ha
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Venedor actualitzat amb èxit',
            'vendor'  => $user
        ], 200);
    }

    /**
     * Elimina un venedor per ID.
     * Aquesta operació només hauria d’estar disponible pel venedor mateix o per l’administrador.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || $user->getTable() !== 'vendors' || $user->id != $id) {
            return response()->json(['message' => 'No autoritzat'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Venedor eliminat correctament'], 200);
    }
}
