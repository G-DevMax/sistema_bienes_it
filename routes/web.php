<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Tickets\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Formulario público de tickets para funcionarios (sin inicio de sesión)
Route::get('tickets/crear', [TicketController::class, 'create'])->name('tickets.create');
Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');

// Rutas accesibles solo cuando NO se ha iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Rutas accesibles solo con sesión activa
use App\Http\Controllers\Bienes\CategoriasController;
use App\Http\Controllers\Bienes\ItemsController;
use App\Http\Controllers\Bienes\MovimientosController;
use App\Http\Controllers\DependenciasController;
use App\Http\Controllers\FuncionariosController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\Informes\DetalleProblemaController;
use App\Http\Controllers\Informes\InformeController;
use App\Http\Controllers\UsuariosController;

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('Bienes.menu_bientes');
    })->name('menu_bientes');

    Route::post('items/{item}/ingresar-stock', [ItemsController::class, 'ingresarStock'])->name('items.ingresar-stock');
    Route::resource('items', ItemsController::class);
    Route::resource('usuarios', UsuariosController::class);
    Route::resource('funcionarios', FuncionariosController::class);
    Route::resource('dependencias', DependenciasController::class);
    Route::resource('categorias', CategoriasController::class);
    Route::resource('informes', InformeController::class);
    Route::get('informes/{informe}/pdf', [InformeController::class, 'exportarPdf'])->name('informes.pdf');
    Route::resource('detalles-problemas', DetalleProblemaController::class);

    // Módulo de Tickets (área del técnico)
    Route::get('tickets', [TicketController::class, 'dashboard'])->name('tickets.dashboard');
    Route::get('tickets/estado-pendientes', [TicketController::class, 'estadoPendientes'])->name('tickets.pendientes-status');
    Route::get('tickets/listado', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/crear-manual', [TicketController::class, 'createManual'])->name('tickets.create-manual');
    Route::post('tickets/crear-manual', [TicketController::class, 'storeManual'])->name('tickets.store-manual');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/asignar', [TicketController::class, 'asignar'])->name('tickets.asignar');
    Route::post('tickets/{ticket}/cerrar', [TicketController::class, 'cerrar'])->name('tickets.cerrar');
    // los movimientos no se editan ni eliminan: se corrigen con devoluciones, bajas o anulaciones
    // debe ir antes del resource para no chocar con movimientos/{movimiento}
    Route::get('movimientos/informe', [MovimientosController::class, 'generarInforme'])->name('movimientos.informe');
    Route::resource('movimientos', MovimientosController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('movimientos/{movimiento}/planilla', [MovimientosController::class, 'exportarPlanilla'])->name('movimientos.planilla');
    Route::post('movimientos/{movimiento}/devolver', [MovimientosController::class, 'devolver'])->name('movimientos.devolver');
    Route::post('movimientos/{movimiento}/baja', [MovimientosController::class, 'bajaEntrega'])->name('movimientos.baja');
    Route::post('movimientos/{movimiento}/anular', [MovimientosController::class, 'anular'])->name('movimientos.anular');
    Route::get('historial/descargar', [HistorialController::class, 'descargar'])->name('historial.descargar');
});
