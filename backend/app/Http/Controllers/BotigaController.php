<?php

namespace App\Http\Controllers;

use App\Models\Botiga;
use App\Models\HorariBotiga;
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
        return response()->json(Botiga::with('horaris')->get());
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
    
        $botigues = Botiga::where('vendor_id', $user->id)
            ->with('horaris')
            ->get();
        
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
            'horaris' => 'nullable|array',
        ]);
    
        $botiga = Botiga::create([
            'vendor_id' => $user->id,
            'nom' => $request->nom,
            'descripcio' => $request->descripcio,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        if ($request->has('horaris')) {
            foreach ($request->horaris as $horari) {
                // Evita errors si falta alguna clau
                if (!is_array($horari) || !isset($horari['dia']) || !isset($horari['franjes'])) continue;
                if (!is_array($horari['franjes'])) continue;
                if (isset($horari['tancat']) && $horari['tancat']) continue;
            
                foreach ($horari['franjes'] as $franja) {
                    // 🔒 Validació extra per evitar errors
                    $obertura = strlen($franja['obertura']) === 5
                        ? $franja['obertura'] . ':00'
                        : $franja['obertura'];

                    $tancament = strlen($franja['tancament']) === 5
                        ? $franja['tancament'] . ':00'
                        : $franja['tancament'];

                    HorariBotiga::create([
                        'botiga_id' => $botiga->id,
                        'dia' => $horari['dia'],
                        'obertura' => $franja['obertura'],
                        'tancament' => $franja['tancament'],
                    ]);
                }
                
            }
            
        }       
    
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
        'horaris' => 'nullable|array',
    ]);

    Log::info("🔄 Actualitzant botiga:", [
        'latitude' => $request->latitude,
        'longitude' => $request->longitude
    ]);

    $botiga->update([
        'nom' => $request->nom,
        'descripcio' => $request->descripcio,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
    ]);

    // 🔥 Debug: Comprovem si hi ha horaris
    if ($request->has('horaris')) {
        Log::info("🕒 Nous horaris rebuts:", $request->horaris);
    } else {
        Log::warning("⚠️ No s'han rebut horaris.");
    }

    // 🔄 Esborrem els horaris antics
    HorariBotiga::where('botiga_id', $botiga->id)->delete();

    if ($request->has('horaris')) {
        try {
            foreach ($request->horaris as $horari) {
                // Evita errors si falta alguna clau
                if (!is_array($horari) || !isset($horari['dia']) || !isset($horari['franjes'])) continue;
                if (!is_array($horari['franjes'])) continue;
                if (isset($horari['tancat']) && $horari['tancat']) continue;
            
                foreach ($horari['franjes'] as $franja) {
                    if (!isset($franja['obertura']) || !isset($franja['tancament'])) continue;
                
                    $obertura = strlen($franja['obertura']) === 5 ? $franja['obertura'] . ':00' : $franja['obertura'];
                    $tancament = strlen($franja['tancament']) === 5 ? $franja['tancament'] . ':00' : $franja['tancament'];
                
                    Log::debug('🔍 Franja a guardar:', [
                        'botiga_id' => $botiga->id,
                        'dia' => $horari['dia'],
                        'obertura' => $obertura,
                        'tancament' => $tancament,
                    ]);
                
                    HorariBotiga::create([
                        'botiga_id' => $botiga->id,
                        'dia' => $horari['dia'],
                        'obertura' => $obertura,
                        'tancament' => $tancament,
                    ]);
                }
                
                
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Error guardant horaris:", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'stack' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Error actualitzant els horaris']);
        }
    }

    return response()->json(['message' => 'Botiga actualitzada amb èxit', 'botiga' => $botiga->load('horaris')], 200);
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
        $botiga = Botiga::with('productes', 'horaris')->find($id);
    
        if (!$botiga) {
            return response()->json(['error' => 'Botiga no trobada'], 404);
        }
    
        return response()->json($botiga);
    }
}
