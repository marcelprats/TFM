<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorAuthController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BotigaController;
use App\Http\Controllers\ImportacioController;
use App\Http\Controllers\ProducteController;
use App\Http\Controllers\ProducteImportController;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/analitza-excel', [ProducteImportController::class, 'analitzaExcel']);
Route::post('/import-productes', [ProducteImportController::class, 'importar']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::post('/import-productes', [ImportacioController::class, 'importar'])->middleware('auth:sanctum');


// Ruta per obtenir l'usuari autenticat
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::get('/users', function () {
    return response()->json(User::all());
});

Route::get('/vendors', function () {
    return response()->json(Vendor::all());
});
Route::get('/vendors', [VendorController::class, 'index']);

// Rutes de botigues (només per a venedors)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/botigues', [BotigaController::class, 'store']);
    Route::put('/botigues/{id}', [BotigaController::class, 'update']);
    Route::delete('/botigues/{id}', [BotigaController::class, 'destroy']);
});

// Rutes de productes (només per a venedors autenticats)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/productes', [ProducteController::class, 'index']);
    Route::post('/productes', [ProducteController::class, 'store']);
    Route::post('/import-productes', [ImportacioController::class, 'importar']);
    Route::put('/productes/{id}', [ProducteController::class, 'update']);
    Route::delete('/productes/{id}', [ProducteController::class, 'destroy']);
});

Route::get('/productes-tots', [ProducteController::class, 'getAllProducts']);
Route::get('/productes/{id}', [ProducteController::class, 'show']);
Route::get('/botigues/{id}', [BotigaController::class, 'show']);
Route::get('/botigues', [BotigaController::class, 'indexPublic']);


Route::get('/vendors/{id}', function ($id) {
    return response()->json(
        Vendor::with('botigues.productes')->findOrFail($id)
    );
});


Route::middleware(['auth:sanctum'])->get('/botigues-mes', [BotigaController::class, 'getBotiguesByAuthVendor']);
