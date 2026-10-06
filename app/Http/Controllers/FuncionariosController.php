<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\funcionarios;
use App\Models\dependencias;

class FuncionariosController extends Controller
{
    public function index()
    {
        $funcionarios = funcionarios::orderby('nombre', 'ASC')->paginate(10);
        $dependencias = dependencias::all();

        return view('funcionarios.index', compact('funcionarios', 'dependencias'));
    }

    public function create()
    {
        $dependencias = dependencias::all();
        return view('funcionarios.create', compact('dependencias'));
    }

    public function store(Request $request)
    {
        $funcionarios = funcionarios::create($request->all());
        $funcionarios->save();
        return redirect()->route('funcionarios.index')->with('success', 'Funcionario creado exitosamente');
    }

    public function show(string $id)
    {
        // Redirigir a index si se intenta acceder a la ruta de show por error (GET /funcionarios/{id})
        return redirect()->route('funcionarios.index');
    }

    public function edit(string $id)
    {
        $funcionarios = funcionarios::find($id);
        $dependencias = dependencias::all();
        return view('funcionarios.edit', compact('funcionarios', 'dependencias'));
    }

    public function update(Request $request, string $id)
    {
        $funcionarios = funcionarios::find($id);
        $funcionarios->update($request->all());
        $funcionarios->save();
        return redirect()->route('funcionarios.index')->with('success', 'Funcionario actualizado exitosamente');
    }

    public function destroy(string $id)
    {
        $funcionarios = funcionarios::find($id);
        $funcionarios->estado_funcionario = 0;
        $funcionarios->save();
        return redirect()->route('funcionarios.index')->with('success', 'Funcionario dado de baja exitosamente');
    }
}
