<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (No requieren inicio de sesión)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas privadas (Requieren estar autenticado con Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Rutas para Cuentas Bancarias
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{account}', [AccountController::class, 'show']);

    // Rutas para Transacciones (Depósitos, Retiros y Transferencias)
    Route::post('/accounts/{account}/deposit', [TransactionController::class, 'deposit']);
    Route::post('/accounts/{account}/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/accounts/{account}/transfer', [TransactionController::class, 'transfer']);

        // Rutas exclusivas para cajero y admin
    Route::middleware('role:cajero,admin')->group(function () {
        Route::get('/admin/accounts', [AccountController::class, 'indexAll']);
    });

        // Rutas exclusivas para admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole']);
    });
});