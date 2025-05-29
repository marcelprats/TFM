<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ReviewsController;

/*
|--------------------------------------------------------------------------
| RUTES D'AUTENTICACIÓ (Clients i Vendors)
|--------------------------------------------------------------------------
*/
Route::post('/register',        [AuthController::class, 'registerUser']);
Route::post('/register-vendor', [AuthController::class, 'registerVendor']);
Route::post('/login',           [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| RUTES PÚBLIQUES (Sense autenticació)
|--------------------------------------------------------------------------
*/
Route::get('/botigues/{botiga}',[BotigaController::class, 'show']);
Route::get('/botigues-mes',     [BotigaController::class, 'botiguesMesPublic']);
Route::get('/vendors',          [VendorController::class, 'indexPublic']);
Route::get('/vendors/{id}',     [VendorController::class, 'showPublic']);
Route::get('/users',            fn() => response()->json(\App\Models\User::all()));
Route::get('/botigues',         [BotigaController::class, 'indexPublic']);
Route::get('/productes',        [ProducteController::class, 'getAllProducts']);
Route::get('/productes/{id}',   [ProducteController::class, 'show']);
Route::patch('/productes/{id}', [ProducteController::class, 'updateStock']);
Route::get('/productes-tots',   [ProducteController::class, 'getAllProducts']);
Route::get('/categories',       [CategoriaController::class, 'index']);
Route::post('/contacte',        [ContactController::class, 'submit']);
Route::get('/reviews',          [ReviewsController::class, 'index']);
Route::get('/productes/{productId}/status', [ReviewsController::class, 'status']);
Route::get('/productes/{productId}/reviews',   [ReviewsController::class, 'byProduct']);
Route::get('/productes/{productId}/store-summary', [ReviewsController::class, 'storeSummary']);
Route::get('/botigues/{botigaId}/store-summary', [ReviewsController::class, 'storeSummary']);
Route::get('/botigues/{botigaId}/reviews',       [ReviewsController::class, 'byStore']);

/*
|--------------------------------------------------------------------------
| RUTES PROTEGIDES (Usuari loguejat via Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Carret
    Route::get('/cart',              [CartController::class, 'index']);
    Route::post('/cart',             [CartController::class, 'addItem']);
    Route::put('/cart/check-stock',  [CartController::class, 'checkStock']);
    Route::put('/cart/{cartItem}',   [CartController::class, 'updateItem']);
    Route::delete('/cart/{cartItem}',[CartController::class, 'removeItem']);
    Route::delete('/cart',           [CartController::class, 'destroy']);
    Route::post('/cart/checkout',    [CartController::class, 'checkout']);


    // Comandes
    Route::get('/orders',            [OrderController::class, 'index']);
    Route::get('/orders/{order}',    [OrderController::class, 'show']);
    Route::post('/orders',           [OrderController::class, 'store']);
    Route::put('/orders/{order}',    [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    //Valoracions
    Route::get('/my-reviews', [ReviewsController::class, 'myReviews']);
    Route::post('/reviews',   [ReviewsController::class, 'store']);

    // Historial de comandes del comprador actual
    Route::get('/my-orders',         [OrderController::class, 'myOrders']);

// RUTES EXCLUSIVES PER VENEDORS
Route::prefix('vendor')->middleware('auth:sanctum')->group(function () {

    // Botigues
    Route::post('/botigues',                    [BotigaController::class, 'store']);
    Route::put('/botigues/{botiga}',            [BotigaController::class, 'update']);
    Route::delete('/botigues/{botiga}',         [BotigaController::class, 'destroy']);
    Route::get('/botigues-mes',                 [BotigaController::class, 'getBotiguesByAuthVendor']);

    // Productes
    Route::get('/productes',                    [ProducteController::class, 'index']);
    Route::post('/productes',                   [ProducteController::class, 'store']);
    Route::put('/productes/{id}',               [ProducteController::class, 'update']);
    Route::patch('/productes/{id}',             [ProducteController::class, 'update']);
    Route::delete('/productes/{id}',            [ProducteController::class, 'destroy']);

    // Importacions
    Route::post('/import-productes',            [ImportacioController::class, 'importar']);
    Route::get('/importacions',                 [ImportRecordController::class, 'indexApi']);
    Route::get('/importacions/{id}',            [ImportRecordController::class, 'showApi']);

    // Comandes que ha rebut el vendor (les seves botigues)
    Route::get('/orders',                       [OrderController::class, 'vendorOrders']);

    // Categories
    Route::get('/categories',                   [CategoriaController::class, 'index']);



    // Perfil
    Route::get('/vendors',                      [VendorController::class, 'index']);
    Route::get('/vendors/{id}',                 [VendorController::class, 'show']);
    Route::put('/vendors/{id}',                 [VendorController::class, 'update']);
    Route::delete('/vendors/{id}',              [VendorController::class, 'destroy']);
    });
});
