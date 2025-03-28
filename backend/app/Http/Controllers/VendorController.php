<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        // Això carregarà automàticament les botigues relacionades de cada venedor
        $vendors = Vendor::with('botigues')->get();

        return response()->json($vendors);
    }
}
