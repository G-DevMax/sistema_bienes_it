<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas accesibles solo cuando NO se ha iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Rutas accesibles solo con sesión activa
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\FuncionariosController;
use App\Http\Controllers\DependenciasController;
use App\Http\Controllers\MovimientosController;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('/login');
    })->name('/login');

    Route::resource('items', ItemsController::class);
    Route::resource('usuarios', UsuariosController::class);
    Route::resource('funcionarios', FuncionariosController::class);
    Route::resource('dependencias', DependenciasController::class);
    Route::resource('movimientos', MovimientosController::class);
});
