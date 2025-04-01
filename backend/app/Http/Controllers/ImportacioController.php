<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\Producte;
use Illuminate\Support\Facades\Storage;

class ImportacioController extends Controller
{
    public function importar(Request $request)
    {
        $request->validate([
            'botiga_id' => 'required|exists:botigues,id'
        ]);
    
        $previewData = json_decode($request->input('preview'), true); // 👈 Aquí!
        $resultats = [
            'importats' => 0,
            'errors' => [],
            'detalls' => []
        ];
    
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
                    'vendor_id' => auth()->id()
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
    
        return response()->json($resultats);
    }
    
}
