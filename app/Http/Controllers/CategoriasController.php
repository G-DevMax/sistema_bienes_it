<?php

namespace App\Http\Controllers;

use App\Models\categorias;
use App\Models\items;
use Illuminate\Http\Request;

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
        $request->validate([
            'nombre' => 'required|string|max:120|unique:categorias,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ]);

        categorias::create($request->only('nombre'));

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

        $request->validate([
            'nombre' => 'required|string|max:120|unique:categorias,nombre,' . $categoria->id,
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ]);

        $categoria->update($request->only('nombre'));

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(string $id)
    {
        if (items::where('categoria_id', $id)->exists()) {
            return redirect()->route('categorias.index')
                ->with('error', 'No se puede eliminar la categoría porque tiene items asociados.');
        }

        $categoria = categorias::findOrFail($id);
        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }
}

