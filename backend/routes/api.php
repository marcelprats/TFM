<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorAuthController;
use App\Http\Controllers\BotigaController;
use App\Http\Controllers\ProducteController;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

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


// Rutes de botigues (només per a venedors)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/botigues', [BotigaController::class, 'index']);
    Route::post('/botigues', [BotigaController::class, 'store']);
    Route::put('/botigues/{id}', [BotigaController::class, 'update']);
    Route::delete('/botigues/{id}', [BotigaController::class, 'destroy']);
});

// Rutes de productes (només per a venedors)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/productes', [ProducteController::class, 'index']);
    Route::post('/productes', [ProducteController::class, 'store']);
    Route::put('/productes/{id}', [ProducteController::class, 'update']);
    Route::delete('/productes/{id}', [ProducteController::class, 'destroy']);
});