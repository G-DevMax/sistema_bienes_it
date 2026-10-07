<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\funcionarios;
use App\Models\dependencias;

class FuncionariosController extends Controller
{
    public function index()
    {
        $funcionarios = funcionarios::with('dependencia')->orderBy('nombre', 'ASC')->paginate(10);
        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();

        return view('funcionarios.index', compact('funcionarios', 'dependencias'));
    }

    public function create()
    {
        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();
        return view('funcionarios.create', compact('dependencias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:120',
            'dependencia_id' => 'required|exists:dependencias,id',
        ], [
            'nombre.required' => 'El nombre del funcionario es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'dependencia_id.required' => 'Debe seleccionar una dependencia.',
            'dependencia_id.exists' => 'La dependencia seleccionada no es válida.',
        ]);

        funcionarios::create([
            'nombre' => $request->nombre,
            'dependencia_id' => $request->dependencia_id,
            'estado_funcionario' => 1,
        ]);

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario registrado exitosamente.');
    }

    public function show(string $id)
    {
        return redirect()->route('funcionarios.index');
    }

    public function edit(string $id)
    {
        $funcionarios = funcionarios::findOrFail($id);
        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();

        return view('funcionarios.edit', compact('funcionarios', 'dependencias'));
    }

    public function update(Request $request, string $id)
    {
        $funcionario = funcionarios::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:120',
            'dependencia_id' => 'required|exists:dependencias,id',
            'estado_funcionario' => 'required|in:0,1',
        ], [
            'nombre.required' => 'El nombre del funcionario es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'dependencia_id.required' => 'Debe seleccionar una dependencia.',
            'dependencia_id.exists' => 'La dependencia seleccionada no es válida.',
            'estado_funcionario.required' => 'Debe definir el estado del funcionario.',
            'estado_funcionario.in' => 'El estado seleccionado no es válido.',
        ]);

        $funcionario->update($request->only('nombre', 'dependencia_id', 'estado_funcionario'));

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        $funcionario = funcionarios::findOrFail($id);
        $funcionario->estado_funcionario = 0;
        $funcionario->save();

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario dado de baja exitosamente.');
    }
}

