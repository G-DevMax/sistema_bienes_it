<?php

namespace App\Http\Controllers;

use App\Models\categorias;
use App\Models\items;
use Illuminate\Http\Request;
use App\Services\LoggerService;
use Illuminate\Support\Facades\Auth;

class CategoriasController extends Controller
{
    public function index()
    {
        $categorias = categorias::withCount('items')->orderBy('nombre', 'ASC')->paginate(10);
        return view('categorias.index', compact('categorias'));
    }

    public function show(string $id)
    {
        return redirect()->route('categorias.index');
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'categorias.index');

        try {
            categorias::create($request->only('nombre'));

            LoggerService::info('Categoría creada', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear categoría', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('categorias.index')->with('error', 'Error al crear categoría: ' . $e->getMessage());
        }

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(string $id)
    {
        $categoria = categorias::findOrFail($id);
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, string $id)
    {
        $categoria = categorias::findOrFail($id);

        $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:categorias,nombre,' . $categoria->id,
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'categorias.index');

        try {
            $categoria->update($request->only('nombre'));

            LoggerService::info('Categoría actualizada', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar categoría', [
                'nombre' => $request->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('categorias.index')->with('error', 'Error al actualizar categoría: ' . $e->getMessage());
        }

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(string $id)
    {
        try {
            if (items::where('categoria_id', $id)->exists()) {
                return redirect()->route('categorias.index')
                    ->with('error', 'No se puede eliminar la categoría porque tiene items asociados.');
            }

            $categoria = categorias::findOrFail($id);
            $categoria->delete();

            LoggerService::info('Categoría eliminada', [
                'nombre' => $categoria->nombre,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al eliminar categoría', [
                'nombre' => $categoria->nombre,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('categorias.index')->with('error', 'Error al eliminar categoría: ' . $e->getMessage());
        }

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}
