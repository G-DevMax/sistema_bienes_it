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
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\MovimientosController;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('/menu_bientes');
    })->name('menu_bientes');

    Route::post('items/{item}/ingresar-stock', [ItemsController::class, 'ingresarStock'])->name('items.ingresar-stock');
    Route::resource('items', ItemsController::class);
    Route::resource('usuarios', UsuariosController::class);
    Route::resource('funcionarios', FuncionariosController::class);
    Route::resource('dependencias', DependenciasController::class);
    Route::resource('categorias', CategoriasController::class);
    // los movimientos no se editan ni eliminan: se corrigen con devoluciones, bajas o anulaciones
    Route::resource('movimientos', MovimientosController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('movimientos/{movimiento}/planilla', [MovimientosController::class, 'exportarPlanilla'])->name('movimientos.planilla');
    Route::post('movimientos/{movimiento}/devolver', [MovimientosController::class, 'devolver'])->name('movimientos.devolver');
    Route::post('movimientos/{movimiento}/baja', [MovimientosController::class, 'bajaEntrega'])->name('movimientos.baja');
    Route::post('movimientos/{movimiento}/anular', [MovimientosController::class, 'anular'])->name('movimientos.anular');
});
