<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

use App\Http\Controllers\ItemsController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\FuncionariosController;
use App\Http\Controllers\DependenciasController;
use App\Http\Controllers\MovimientosController;

Route::resource('items', ItemsController::class);
Route::resource('usuarios', UsuariosController::class);
Route::resource('funcionarios', FuncionariosController::class);
Route::resource('dependencias', DependenciasController::class);
Route::resource('movimientos', MovimientosController::class);
