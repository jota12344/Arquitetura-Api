<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

// Rota pública de autenticação
Route::post('/login', [AuthController::class, 'login'])
    ->name('auth.login');

// Rota protegida: retorna o usuário autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rotas públicas: qualquer pessoa pode consultar produtos
Route::get('/products', [ProductController::class, 'index'])
    ->name('product.index');

Route::get('/products/{id}', [ProductController::class, 'show'])
    ->name('product.show');

// Rotas protegidas: exigem autenticação
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/products', [ProductController::class, 'store'])
        ->name('product.store');

    Route::put('/products/{id}', [ProductController::class, 'update'])
        ->name('product.update');

    Route::delete('/products/{id}', [ProductController::class, 'destroy'])
        ->name('product.destroy');

    Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout');

});