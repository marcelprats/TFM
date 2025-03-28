<?php

namespace App\Http\Controllers;

use App\Models\Producte;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ProducteImportController extends Controller
{
    public function analitzaExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'Fitxer no trobat'], 400);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $headers[] = trim((string) $cell->getValue());
            }
        }

        return response()->json(['headers' => $headers]);
    }

    public function importar(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls',
                'botiga_id' => 'required|integer',
                'mapping' => 'required|json'
            ]);

            $file = $request->file('file');
            $mapping = json_decode($request->input('mapping'), true);
            $categoriaComuna = $request->input('categoria');

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // Primera fila = capçaleres
            $headers = array_shift($rows);
            $indexToField = [];

            foreach ($headers as $col => $headerLabel) {
                $headerLabel = trim($headerLabel);
                if (isset($mapping[$headerLabel]) && $mapping[$headerLabel]) {
                    $indexToField[$col] = $mapping[$headerLabel];
                }
            }

            $importats = 0;
            foreach ($rows as $row) {
                $data = [
                    'botiga_id' => $request->botiga_id,
                ];

                foreach ($indexToField as $col => $field) {
                    $value = trim((string) $row[$col]);
                    if ($field === 'preu' || $field === 'stock') {
                        $value = is_numeric($value) ? floatval($value) : 0;
                    }
                    $data[$field] = $value;
                }

                if ($categoriaComuna && empty($data['categoria'])) {
                    $data['categoria'] = $categoriaComuna;
                }

                if (empty($data['nom']) || !isset($data['preu']) || !isset($data['stock'])) {
                    Log::warning('Producte amb dades incompletes', $data);
                    continue;
                }

                Producte::create($data);
                $importats++;
            }

            return response()->json([
                'message' => "$importats productes importats correctament."
            ]);

        } catch (\Exception $e) {
            Log::error('Error durant la importació:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'Error durant la importació.'], 500);
        }
    }
}
