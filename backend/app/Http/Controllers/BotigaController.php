<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBotigaRequest;
use App\Http\Requests\UpdateBotigaRequest;
use App\Models\Botiga;
use App\Models\HorariBotiga;
use Illuminate\Http\JsonResponse;

class BotigaController extends Controller
{
    public function __construct()
    {
        \Auth::shouldUse('vendor');
    }

    /** GET /botigues (públic) */
    public function indexPublic(): JsonResponse
    {
        $botigues = Botiga::with('horaris')->get();
        return response()->json($botigues);
    }

    /** GET /botigues-mes (públic) */
    public function botiguesMesPublic(): JsonResponse
    {
        return $this->indexPublic();
    }

    /** GET /vendor/botigues */
    public function getBotiguesByAuthVendor(): JsonResponse
    {
        $vendor = auth()->user();
        $botigues = Botiga::where('vendor_id', $vendor->id)
            ->with('horaris')
            ->get();

        return response()->json($botigues);
    }

    /** POST /vendor/botigues */
    public function store(StoreBotigaRequest $request): JsonResponse
    {
        $vendor = $request->user();
        $data = $request->validated();

        $botiga = $vendor->botigues()->create([
            'nom' => $data['nom'],
            'descripcio' => $data['descripcio'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        $this->syncHoraris($botiga, $data['horaris'] ?? []);

        return response()->json($botiga->load('horaris'), 201);
    }

    /** PUT /vendor/botigues/{botiga} */
    public function update(UpdateBotigaRequest $request, Botiga $botiga): JsonResponse
    {
        $this->authorize('update', $botiga);

        $data = $request->validated();

        $botiga->update([
            'nom' => $data['nom'],
            'descripcio' => $data['descripcio'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        HorariBotiga::where('botiga_id', $botiga->id)->delete();
        $this->syncHoraris($botiga, $data['horaris'] ?? []);

        return response()->json($botiga->load('horaris'));
    }

    /** DELETE /vendor/botigues/{botiga} */
    public function destroy(Botiga $botiga): JsonResponse
    {
        $this->authorize('delete', $botiga);
        
        $botiga->delete();
        return response()->json(['message' => 'Botiga eliminada correctament']);
    }

    /** GET /botigues/{id} */
    public function show(Botiga $botiga): JsonResponse
    {
        $botiga->load(['productes', 'horaris']);
        return response()->json($botiga);
    }

    /**
     * Helpers
     */
    protected function syncHoraris(Botiga $botiga, array $horaris): void
    {
        foreach ($horaris as $h) {
            if (empty($h['dia']) || empty($h['franjes']) || ! is_array($h['franjes'])) {
                continue;
            }
            foreach ($h['franjes'] as $f) {
                HorariBotiga::create([
                    'botiga_id'  => $botiga->id,
                    'dia'        => $h['dia'],
                    'obertura'   => strlen($f['obertura']) === 5 ? $f['obertura'].':00' : $f['obertura'],
                    'tancament'  => strlen($f['tancament']) === 5 ? $f['tancament'].':00' : $f['tancament'],
                ]);
            }
        }
    }
}
