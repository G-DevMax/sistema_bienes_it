<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use Illuminate\Http\Request;

class DependenciasController extends Controller
{
    public function index()
    {
        $dependencias = dependencias::orderby('nombre', 'ASC')->paginate(10);

        return view('dependencias.index', compact('dependencias'));
    }

    public function create()
    {
        return view('dependencias.create');
    }

    public function edit(string $id)
    {
        return view('dependencias.edit', compact('id'));
    }
}
