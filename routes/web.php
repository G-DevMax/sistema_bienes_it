<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Bienes\CategoriasController;
use App\Http\Controllers\Bienes\ItemsController;
use App\Http\Controllers\Bienes\MovimientosController;
use App\Http\Controllers\DependenciasController;
use App\Http\Controllers\FuncionariosController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\Informes\DetalleProblemaController;
use App\Http\Controllers\Informes\InformeController;
use App\Http\Controllers\Tickets\TicketController;
use App\Http\Controllers\UsuariosController;
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
Route::middleware(['auth', 'usuario.activo'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('can:acceso.sistema')->group(function () {
        Route::get('/dashboard', function () {
            return view('Bienes.menu_bientes');
        })->name('menu_bientes');

        // Registra CRUD en orden seguro (create antes de {id}) con permiso por acción
        $recursoPorAccion = static function (string $nombre, string $controlador, string $ver, string $crear, string $editar, string $eliminar): void {
            Route::resource($nombre, $controlador)->only(['create', 'store'])->middleware("can:{$crear}");
            Route::resource($nombre, $controlador)->only(['edit', 'update'])->middleware("can:{$editar}");
            Route::resource($nombre, $controlador)->only(['destroy'])->middleware("can:{$eliminar}");
            Route::resource($nombre, $controlador)->only(['index', 'show'])->middleware("can:{$ver}");
        };

        // Usuarios e Historial (solo Administrador IT)
        Route::resource('usuarios', UsuariosController::class)->middleware('can:usuarios.gestionar');
        Route::get('historial/descargar', [HistorialController::class, 'descargar'])
            ->name('historial.descargar')
            ->middleware('can:historial.descargar');

        // Módulo de Bienes
        Route::post('items/{item}/ingresar-stock', [ItemsController::class, 'ingresarStock'])
            ->name('items.ingresar-stock')
            ->middleware('can:bienes.crear');
        $recursoPorAccion('items', ItemsController::class, 'bienes.ver', 'bienes.crear', 'bienes.editar', 'bienes.eliminar');
        $recursoPorAccion('categorias', CategoriasController::class, 'bienes.ver', 'bienes.crear', 'bienes.editar', 'bienes.eliminar');
        $recursoPorAccion('dependencias', DependenciasController::class, 'bienes.ver', 'bienes.crear', 'bienes.editar', 'bienes.eliminar');
        $recursoPorAccion('funcionarios', FuncionariosController::class, 'funcionarios.ver', 'funcionarios.gestionar', 'funcionarios.gestionar', 'funcionarios.gestionar');

        // Los movimientos no se editan ni eliminan: se corrigen con devoluciones, bajas o anulaciones
        // Las rutas estáticas van antes de movimientos/{movimiento}
        Route::get('movimientos/informe', [MovimientosController::class, 'generarInforme'])
            ->name('movimientos.informe')
            ->middleware('can:bienes.ver');
        Route::resource('movimientos', MovimientosController::class)->only(['create', 'store'])->middleware('can:bienes.crear');
        Route::get('movimientos/{movimiento}/planilla', [MovimientosController::class, 'exportarPlanilla'])
            ->name('movimientos.planilla')
            ->middleware('can:bienes.ver');
        Route::post('movimientos/{movimiento}/devolver', [MovimientosController::class, 'devolver'])
            ->name('movimientos.devolver')
            ->middleware('can:bienes.crear');
        Route::post('movimientos/{movimiento}/baja', [MovimientosController::class, 'bajaEntrega'])
            ->name('movimientos.baja')
            ->middleware('can:bienes.eliminar');
        Route::post('movimientos/{movimiento}/anular', [MovimientosController::class, 'anular'])
            ->name('movimientos.anular')
            ->middleware('can:bienes.eliminar');
        Route::resource('movimientos', MovimientosController::class)->only(['index', 'show'])->middleware('can:bienes.ver');

        // Módulo de Informes y Problemas Comunes
        $recursoPorAccion('informes', InformeController::class, 'informes.ver', 'informes.crear', 'informes.editar', 'informes.eliminar');
        Route::get('informes/{informe}/pdf', [InformeController::class, 'exportarPdf'])
            ->name('informes.pdf')
            ->middleware('can:informes.ver');
        $recursoPorAccion('detalles-problemas', DetalleProblemaController::class, 'detalles.ver', 'detalles.gestionar', 'detalles.gestionar', 'detalles.gestionar');

        // Módulo de Tickets (área técnica)
        Route::middleware('can:tickets.ver')->group(function () {
            Route::get('tickets', [TicketController::class, 'dashboard'])->name('tickets.dashboard');
            Route::get('tickets/estado-pendientes', [TicketController::class, 'estadoPendientes'])->name('tickets.pendientes-status');
            Route::get('tickets/listado', [TicketController::class, 'index'])->name('tickets.index');
        });

        Route::middleware('can:tickets.crear')->group(function () {
            Route::get('tickets/crear-manual', [TicketController::class, 'createManual'])->name('tickets.create-manual');
            Route::post('tickets/crear-manual', [TicketController::class, 'storeManual'])->name('tickets.store-manual');
        });

        Route::get('tickets/{ticket}', [TicketController::class, 'show'])
            ->name('tickets.show')
            ->middleware('can:tickets.ver');
        Route::post('tickets/{ticket}/asignar', [TicketController::class, 'asignar'])
            ->name('tickets.asignar')
            ->middleware('can:tickets.operar,ticket');
        Route::post('tickets/{ticket}/cerrar', [TicketController::class, 'cerrar'])
            ->name('tickets.cerrar')
            ->middleware('can:tickets.operar,ticket');
    });
});
