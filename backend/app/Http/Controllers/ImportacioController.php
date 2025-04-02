<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\Producte;
use App\Models\Importacio; // Assegura't d'importar el model d'importacions
use Illuminate\Support\Facades\Storage;

class ImportacioController extends Controller
{
    public function importar(Request $request)
    {
        $request->validate([
            'botiga_id' => 'required|exists:botigues,id'
        ]);

        $previewData = json_decode($request->input('preview'), true);
        $resultats = [
            'importats' => 0,
            'errors' => [],
            'detalls' => []
        ];

        // Creem un registre d'importació (poden afegir més camps si cal)
        $importRecord = Importacio::create([
            'vendor_id' => auth()->id(),
            'botiga_id' => $request->botiga_id,
            'fitxer' => $request->hasFile('fitxer') ? $request->file('fitxer')->getClientOriginalName() : null,
            // Inicialitzem altres camps si ho desitges
            'total_importats' => 0,
            'total_errors' => 0,
            'errors' => null,
            'observacions' => null,
        ]);

        foreach ($previewData as $index => $fila) {
            $errorFila = [];

            $nom = trim($fila['nom'] ?? '');
            $descripcio = trim($fila['descripcio'] ?? '');
            $imatge = trim($fila['imatge'] ?? '');
            $categoria = trim($fila['categoria'] ?? '');
            $subcategoria = trim($fila['subcategoria'] ?? '');
            $preuRaw = str_replace(',', '.', strval($fila['preu'] ?? ''));
            $stockRaw = strval($fila['stock'] ?? '');

            if (empty($nom)) $errorFila['nom'] = 'Camp obligatori';
            if (!is_numeric($preuRaw)) $errorFila['preu'] = 'Preu no vàlid';
            if (!preg_match('/^\d+$/', $stockRaw)) $errorFila['stock'] = 'Stock no vàlid (ha de ser un enter sense decimals)';

            if (!empty($errorFila)) {
                $resultats['errors'][] = [
                    'fila' => $index + 2,
                    'errors' => $errorFila,
                    'valors' => $fila
                ];
                continue;
            }

            try {
                Producte::create([
                    'nom' => $nom,
                    'descripcio' => $descripcio,
                    'preu' => floatval($preuRaw),
                    'stock' => intval($stockRaw),
                    'imatge' => $imatge,
                    'categoria' => $categoria,
                    'subcategoria' => $subcategoria,
                    'botiga_id' => $request->botiga_id,
                    'vendor_id' => auth()->id(),
                    'importacio_id' => $importRecord->id
                ]);
                $resultats['importats']++;
            } catch (\Exception $e) {
                $resultats['errors'][] = [
                    'fila' => $index + 2,
                    'errors' => ['exception' => $e->getMessage()],
                    'valors' => $fila
                ];
            }
        }

        // Actualitzem el registre d'importació amb els totals
        $importRecord->update([
            'total_importats' => $resultats['importats'],
            'total_errors' => count($resultats['errors']),
            'errors' => json_encode($resultats['errors']),
        ]);

        // Al final, retornar també l'importació
        $resultats['importacio_id'] = $importRecord->id;

        return response()->json($resultats);
    }
}
