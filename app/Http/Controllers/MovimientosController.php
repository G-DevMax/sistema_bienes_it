<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MovimientosController extends Controller
{
    public function index()
    {
        return view('movimientos.index');
    }

    public function create()
    {
        return view('movimientos.create');
    }

    public function edit(string $id)
    {
        return view('movimientos.edit', compact('id'));
    }
}
