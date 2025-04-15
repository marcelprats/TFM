<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BotigaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ImportacioController;
use App\Http\Controllers\ImportRecordController;
use App\Http\Controllers\ReserveController;
use App\Http\Controllers\ProducteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquest fitxer defineix totes les rutes de la teva API.
|
*/

/** RUTES D'AUTENTICACIÓ **/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

/** RUTES PÚBLICES **/
Route::get('/botigues-mes', [BotigaController::class, 'botiguesMes']);

// La ruta pública per mostrar la llista de venedors (sense necessitat d'autenticació)
Route::get('/vendors', [VendorController::class, 'index']); 

Route::get('/users', fn() => response()->json(\App\Models\User::all()));
Route::get('/botigues/{id}', [BotigaController::class, 'show']);
Route::get('/botigues', [BotigaController::class, 'indexPublic']);
Route::get('/productes-tots', [ProducteController::class, 'getAllProducts']);
Route::get('/productes/{id}', [ProducteController::class, 'show']);
Route::patch('/productes/{id}', [ProducteController::class, 'updateStock']);

/** RUTES COMUNS (per Clients i Venedors) **/
Route::middleware(['auth:sanctum'])->group(function () {
    // Carret i comandes (comú per tots)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'addItem']);
    Route::put('/cart/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'destroy']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}', [OrderController::class, 'update']);
    Route::get('/orders', [OrderController::class, 'index']);
});

/** RUTES EXCLUSIVES PER A VENEDORS **/
Route::middleware(['auth:vendor'])->group(function () {

    // Rutes per a la gestió de botigues i productes
    Route::post('/botigues', [BotigaController::class, 'store']);
    Route::put('/botigues/{id}', [BotigaController::class, 'update']);
    Route::delete('/botigues/{id}', [BotigaController::class, 'destroy']);

    Route::get('/productes', [ProducteController::class, 'index']);
    Route::post('/productes', [ProducteController::class, 'store']);
    Route::put('/productes/{id}', [ProducteController::class, 'update']);
    Route::patch('/productes/{id}', [ProducteController::class, 'update']);
    Route::delete('/productes/{id}', [ProducteController::class, 'destroy']);

    Route::post('/import-productes', [ImportacioController::class, 'importar']);
    Route::get('/importacions', [ImportRecordController::class, 'indexApi']);
    Route::get('/importacions/{id}', [ImportRecordController::class, 'showApi']);

    Route::get('/botigues-mes', [BotigaController::class, 'getBotiguesByAuthVendor']);

    // Rutes per obtenir les comandes específiques dels venedors
    Route::get('/vendor/orders', [OrderController::class, 'vendorOrders']);

    // Opcional: Si vols tenir rutes protegides per la gestió de venedors, pots definir-les
    // Nota: No repetim la ruta pública /vendors, ja que aquest servei ja està disponible públicament.
    Route::get('/vendors/{id}', [VendorController::class, 'show']);
    Route::put('/vendors/{id}', [VendorController::class, 'update']);
    Route::delete('/vendors/{id}', [VendorController::class, 'destroy']);
});
