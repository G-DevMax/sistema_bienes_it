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

Route::resource('items', ItemsController::class);
Route::resource('usuarios', UsuariosController::class);
