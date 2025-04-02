<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Importacio;
use App\Models\Producte;

class ImportRecordController extends Controller
{
    // Mostra la llista de totes les importacions per al venedor logejat
    public function indexApi(Request $request)
    {
        $vendorId = auth()->id();
        $importRecords = Importacio::where('vendor_id', $vendorId)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($importRecords);
    }
    
    // Aquest mètode retorna en format JSON el detall d'una importació concreta i els seus productes
    public function showApi($id)
    {
        $vendorId = auth()->id();
        $importRecord = Importacio::where('vendor_id', $vendorId)->findOrFail($id);
        $products = Producte::where('importacio_id', $id)->get();
        return response()->json([
            'importRecord' => $importRecord,
            'products' => $products,
        ]);
    }
}
