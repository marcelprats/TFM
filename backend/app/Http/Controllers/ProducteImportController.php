<?php

namespace App\Http\Controllers;

use App\Models\Producte;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ProducteImportController extends Controller
{
    /**
     * Retorna els headers del fitxer Excel.
     */
    public function analitzaExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Fitxer no trobat'], 400);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [];

        // Recorrem la primera fila per obtenir les capçaleres
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $headers[] = trim((string)$cell->getValue());
            }
        }

        return response()->json(['headers' => $headers]);
    }

    /**
     * Importa els productes des del fitxer Excel.
     * Aquest mètode utilitza la previsualització (preview) enviada com a JSON
     * i fa fallback als camps comuns (categoria i subcategoria) si alguna fila no en té.
     */
    public function importar(Request $request)
    {
        try {
            // Validació bàsica
            $request->validate([
                'file'        => 'required|file|mimes:xlsx,xls',
                'botiga_id'   => 'required|integer',
                'mapping'     => 'required|json',
                'preview'     => 'required|string',
            ]);

            // Decodifiquem el preview
            $previewJson = $request->input('preview');
            $preview = json_decode($previewJson, true);
            if (!is_array($preview)) {
                return response()->json(['error' => 'Preview invàlid'], 400);
            }

            $botiga_id = $request->input('botiga_id');
            // Camps globals enviats pel formulari (pas 3)
            $categoriaComuna = trim((string)$request->input('categoria'));
            $subcategoriaComuna = trim((string)$request->input('subcategoria'));

            $importats = 0;
            foreach ($preview as $index => $rowData) {
                $errorFila = [];

                $nom = trim((string)($rowData['nom'] ?? ''));
                $descripcio = trim((string)($rowData['descripcio'] ?? ''));
                $imatge = trim((string)($rowData['imatge'] ?? ''));
                // Processar categoria: si el valor del preview és vàlid, l'utilitzem, si no, fem fallback al global;
                // Finalment, si encara no hi ha valor, li assignem null.
                $categoriaRaw = isset($rowData['categoria']) ? trim((string)$rowData['categoria']) : '';
                $categoria = strlen($categoriaRaw) > 0
                    ? intval($categoriaRaw)
                    : (strlen($categoriaComuna) > 0 ? intval($categoriaComuna) : null);

                // Processar subcategoria amb la mateixa lògica
                $subcategoriaRaw = isset($rowData['subcategoria']) ? trim((string)$rowData['subcategoria']) : '';
                $subcategoria = strlen($subcategoriaRaw) > 0
                    ? intval($subcategoriaRaw)
                    : (strlen($subcategoriaComuna) > 0 ? intval($subcategoriaComuna) : null);

                $preuRaw = str_replace(',', '.', strval($rowData['preu'] ?? ''));
                $stockRaw = strval($rowData['stock'] ?? '');

                if (empty($nom)) {
                    $errorFila['nom'] = 'Camp obligatori';
                }
                if (!is_numeric($preuRaw)) {
                    $errorFila['preu'] = 'Preu no vàlid';
                }
                if (!preg_match('/^\d+$/', $stockRaw)) {
                    $errorFila['stock'] = 'Stock no vàlid (ha de ser un enter sense decimals)';
                }

                if (!empty($errorFila)) {
                    $resultats['errors'][] = [
                        'fila' => $index + 2,
                        'errors' => $errorFila,
                        'valors' => $rowData
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
                        'categoria' => $categoria,       // ara és un enter o null
                        'subcategoria' => $subcategoria,   // ara és un enter o null
                        'botiga_id' => $botiga_id,
                        'vendor_id' => auth()->id()
                    ]);
                    $importats++;
                } catch (\Exception $e) {
                    $resultats['errors'][] = [
                        'fila' => $index + 2,
                        'errors' => ['exception' => $e->getMessage()],
                        'valors' => $rowData
                    ];
                }
            }

            return response()->json([
                'message' => "$importats productes importats correctament."
            ]);
        } catch (\Exception $e) {
            \Log::error('Error durant la importació:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json(['error' => 'Error durant la importació.'], 500);
        }
    }

}
