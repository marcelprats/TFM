<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorAuthController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BotigaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ImportacioController; // Ús aquest per la importació
use App\Http\Controllers\ImportRecordController; 
use App\Http\Controllers\ProducteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí definim totes les rutes de la nostra API.
|
*/

// Rutes d'autenticació
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Rutes públiques
Route::get('/botigues-mes', [BotigaController::class, 'botiguesMes']);
Route::get('/vendors', [VendorController::class, 'index']);
Route::get('/users', fn() => response()->json(\App\Models\User::all()));

// Rutes per a les categories
Route::middleware('auth:sanctum')->get('/categories', [CategoriaController::class, 'index']);

// Rutes públiques per a botigues (per a mostrar informació)
Route::get('/botigues/{id}', [BotigaController::class, 'show']);
Route::get('/botigues', [BotigaController::class, 'indexPublic']);

// Rutes per a venedors (amb autenticació)
Route::middleware(['auth:sanctum'])->group(function () {
    // Botigues
    Route::post('/botigues', [BotigaController::class, 'store']);
    Route::put('/botigues/{id}', [BotigaController::class, 'update']);
    Route::delete('/botigues/{id}', [BotigaController::class, 'destroy']);

    // Productes
    Route::get('/productes', [ProducteController::class, 'index']);
    Route::post('/productes', [ProducteController::class, 'store']);
    Route::put('/productes/{id}', [ProducteController::class, 'update']);
    Route::patch('/productes/{id}', [ProducteController::class, 'update']);
    Route::delete('/productes/{id}', [ProducteController::class, 'destroy']);

    // Importació de productes (una única ruta per a importar)
    Route::post('/import-productes', [ImportacioController::class, 'importar']);

    // Registre d'importació de productes
    Route::get('/importacions', [ImportRecordController::class, 'indexApi']);
    Route::get('/importacions/{id}', [ImportRecordController::class, 'showApi']);

    // Botigues per a venedors autenticats
    Route::get('/botigues-mes', [BotigaController::class, 'getBotiguesByAuthVendor']);
});

// Altres rutes per a productes
Route::get('/productes-tots', [ProducteController::class, 'getAllProducts']);
Route::get('/productes/{id}', [ProducteController::class, 'show']);

// Rutes per a venedors amb les seves botigues i productes
Route::get('/vendors/{id}', function ($id) {
    return response()->json(
        \App\Models\Vendor::with('botigues.productes')->findOrFail($id)
    );
});
