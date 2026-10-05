<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\funcionarios;

class FuncionariosController extends Controller
{
    public function index()
    {
        $funcionarios = funcionarios::orderby('nombre', 'ASC')->paginate(10);

        return view('funcionarios.index', compact('funcionarios'));
    }

    public function create()
    {
        return view('funcionarios.create');
    }

    public function edit(string $id)
    {
        return view('funcionarios.edit', compact('id'));
    }
}
