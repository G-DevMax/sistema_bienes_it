<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

use App\Http\Controllers\ItemsController;
Route::resource('items', ItemsController::class);
