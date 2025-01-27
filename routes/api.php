<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\userController;
use Illuminate\Container\Attributes\Auth;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AdminMiddleware;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::apiResource('users', userController::class)->middleware(AuthMiddleware::class);

//Rutas de la API con Operaciones CRUD
Route::post('users', [userController::class, 'store']);
Route::get('users', [userController::class, 'index']);
Route::get('users/{id}', [userController::class, 'show']);
Route::put('users/{id}', [userController::class, 'update'])->middleware(AuthMiddleware::class);
Route::delete('users/{id}', [userController::class, 'destroy'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);

//Ruta para el login
Route::post('users/login', [userController::class, 'login']);

//Ruta para obtener las estadísticas de los registros
Route::get('stats', [userController::class, 'stats'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
