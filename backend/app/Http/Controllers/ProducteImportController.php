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
                'preview'     => 'required|string', // La previsualització enviada com a JSON
            ]);

            // Decodifiquem el preview
            $previewJson = $request->input('preview');
            $preview = json_decode($previewJson, true);
            if (!is_array($preview)) {
                return response()->json(['error' => 'Preview invàlid'], 400);
            }

            $botiga_id = $request->input('botiga_id');
            // Camps comuns enviats pel formulari (Step 3)
            $categoriaComuna    = $request->input('categoria');
            $subcategoriaComuna = $request->input('subcategoria');

            $importats = 0;
            foreach ($preview as $rowData) {
                // Processa cada fila del preview
                $data = [
                    'botiga_id'   => $botiga_id,
                    'nom'         => isset($rowData['nom']) ? trim((string)$rowData['nom']) : '',
                    'descripcio'  => isset($rowData['descripcio']) ? trim((string)$rowData['descripcio']) : '',
                    'preu'        => isset($rowData['preu']) ? floatval($rowData['preu']) : 0,
                    'stock'       => isset($rowData['stock']) ? intval($rowData['stock']) : 0,
                    'imatge'      => isset($rowData['imatge']) ? trim((string)$rowData['imatge']) : '',
                    // Utilitzem el valor del preview si conté una subcategoria vàlida,
                    // sinó, fem fallback al valor comú rebut.
                    'categoria'   => (isset($rowData['categoria']) && strlen(trim((string)$rowData['categoria'])) > 0)
                                        ? $rowData['categoria']
                                        : $categoriaComuna,
                    'subcategoria'=> (isset($rowData['subcategoria']) && strlen(trim((string)$rowData['subcategoria'])) > 0)
                                        ? $rowData['subcategoria']
                                        : $subcategoriaComuna,
                ];

                // Comprovem que els camps obligatoris tinguin valor
                if (empty($data['nom']) || $data['preu'] == 0 || !isset($data['stock'])) {
                    Log::warning('Producte amb dades incompletes', $data);
                    continue;
                }

                // Log per depuració
                Log::debug('Dades per crear producte', $data);

                Producte::create($data);
                $importats++;
            }

            return response()->json([
                'message' => "$importats productes importats correctament."
            ]);
        } catch (\Exception $e) {
            Log::error('Error durant la importació:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json(['error' => 'Error durant la importació.'], 500);
        }
    }
}
