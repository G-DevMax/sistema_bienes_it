<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Models\funcionarios;
use Illuminate\Http\Request;
use App\Services\LoggerService;
use Illuminate\Support\Facades\Auth;

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
        $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:dependencias,nombre',
        ], [
            'nombre.required' => 'El nombre de la dependencia es obligatorio.',
            'nombre.unique' => 'Ya existe una dependencia con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'dependencias.index');

        try {
            dependencias::create($request->only('nombre'));

            LoggerService::info('Dependencia creada', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear dependencia', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('dependencias.index')->with('error', 'Error al crear dependencia: ' . $e->getMessage());
        }

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

        $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:dependencias,nombre,' . $dependencia->id,
        ], [
            'nombre.required' => 'El nombre de la dependencia es obligatorio.',
            'nombre.unique' => 'Ya existe una dependencia con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'dependencias.index');

        try {
            $dependencia->update($request->only('nombre'));

            LoggerService::info('Dependencia actualizada', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('dependencias.index')->with('success', 'Dependencia actualizada exitosamente.');
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar dependencia', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('dependencias.index')->with('error', 'Error al actualizar dependencia: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        try {
            if (funcionarios::where('dependencia_id', $id)->exists()) {
                return redirect()->route('dependencias.index')->with('error', 'No se puede eliminar la dependencia porque tiene funcionarios asignados.');
            }

            $dependencia = dependencias::findOrFail($id);
            $dependencia->delete();

            LoggerService::info('Dependencia eliminada', [
                'nombre' => $dependencia->nombre,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al eliminar dependencia', [
                'nombre' => $dependencia->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('dependencias.index')->with('error', 'Error al eliminar dependencia: ' . $e->getMessage());
        }

        return redirect()->route('dependencias.index')->with('success', 'Dependencia eliminada exitosamente.');
    }
}
