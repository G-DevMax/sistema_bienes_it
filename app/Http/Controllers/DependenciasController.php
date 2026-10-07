<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Models\funcionarios;
use Illuminate\Http\Request;

class DependenciasController extends Controller
{
    public function index()
    {
        $dependencias = dependencias::withCount('funcionarios')->orderBy('nombre', 'ASC')->paginate(10);

        return view('dependencias.index', compact('dependencias'));
    }

    public function show(string $id)
    {
        return redirect()->route('dependencias.index');
    }

    public function create()
    {
        return view('dependencias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:120|unique:dependencias,nombre',
        ], [
            'nombre.required' => 'El nombre de la dependencia es obligatorio.',
            'nombre.unique' => 'Ya existe una dependencia con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ]);

        dependencias::create($request->only('nombre'));

        return redirect()->route('dependencias.index')->with('success', 'Dependencia creada exitosamente.');
    }

    public function edit(string $id)
    {
        $dependencia = dependencias::findOrFail($id);
        return view('dependencias.edit', compact('dependencia'));
    }

    public function update(Request $request, string $id)
    {
        $dependencia = dependencias::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:120|unique:dependencias,nombre,' . $dependencia->id,
        ], [
            'nombre.required' => 'El nombre de la dependencia es obligatorio.',
            'nombre.unique' => 'Ya existe una dependencia con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ]);

        $dependencia->update($request->only('nombre'));

        return redirect()->route('dependencias.index')->with('success', 'Dependencia actualizada exitosamente.');
    }

    public function destroy(string $id)
    {
        if (funcionarios::where('dependencia_id', $id)->exists()) {
            return redirect()->route('dependencias.index')->with('error', 'No se puede eliminar la dependencia porque tiene funcionarios asignados.');
        }

        $dependencia = dependencias::findOrFail($id);
        $dependencia->delete();

        return redirect()->route('dependencias.index')->with('success', 'Dependencia eliminada exitosamente.');
    }
}

