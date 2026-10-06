<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Models\funcionarios;
use Illuminate\Http\Request;

class DependenciasController extends Controller
{
    public function index()
    {
        $dependencias = dependencias::orderby('nombre', 'ASC')->paginate(10);

        return view('dependencias.index', compact('dependencias'));
    }

    public function show(string $id)
    {
        return view('dependencias.show', compact('id'));
    }

    public function create()
    {
        return view('dependencias.create');
    }

    public function store(Request $request)
    {
        $nueva_dependencia = dependencias::create($request->all());
        $nueva_dependencia->save();

        return redirect()->route('dependencias.index')->with('success', 'Dependencia creada exitosamente');
    }

    public function edit(string $id)
    {
        $dependencia = dependencias::find($id);
        return view('dependencias.edit', compact('dependencia'));
    }

    public function update(Request $request, string $id)
    {
        $dependencia = dependencias::find($id);
        $dependencia->update($request->all());
        $dependencia->save();

        return redirect()->route('dependencias.index')->with('success', 'Dependencia actualizada exitosamente');
    }

    public function destroy(string $id)
    {
        if (funcionarios::where('dependencia_id', $id)->exists()) {
            return redirect()->route('dependencias.index')->with('error', 'No se puede eliminar la dependencia porque tiene funcionarios asignados');
        }

        $dependencia = dependencias::find($id);
        $dependencia->delete();

        return redirect()->route('dependencias.index')->with('success', 'Dependencia eliminada exitosamente');
    }
}
