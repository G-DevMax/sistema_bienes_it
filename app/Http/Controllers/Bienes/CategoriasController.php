<?php

namespace App\Http\Controllers\Bienes;

use App\Http\Controllers\Controller;
use App\Models\Bienes\categorias;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriasController extends Controller
{
    public function index()
    {
        $categorias = categorias::withCount('items')->orderBy('nombre', 'ASC')->paginate(10);

        return view('Bienes.categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('Bienes.categorias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarNombre($request);

        return $this->ejecutarAccion(function () use ($datos): void {
            categorias::create([
                'nombre' => Str::title($datos['nombre']),
            ]);

            LoggerService::info('Categoría creada', [
                'nombre' => $datos['nombre'],
            ]);
        }, 'categorias.index', 'Categoría creada exitosamente.', 'No se pudo crear la categoría.', 'Error al crear categoría', [
            'nombre' => $request->nombre,
        ]);
    }

    public function edit(categorias $categoria)
    {
        return view('Bienes.categorias.edit', compact('categoria'));
    }

    public function update(Request $request, categorias $categoria): RedirectResponse
    {
        $datos = $this->validarNombre($request, $categoria->id);

        return $this->ejecutarAccion(function () use ($categoria, $datos): void {
            $categoria->update([
                'nombre' => Str::title($datos['nombre']),
            ]);

            LoggerService::info('Categoría actualizada', [
                'nombre' => $datos['nombre'],
            ]);
        }, 'categorias.index', 'Categoría actualizada exitosamente.', 'No se pudo actualizar la categoría.', 'Error al actualizar categoría', [
            'nombre' => $request->nombre,
        ]);
    }

    public function destroy(categorias $categoria): RedirectResponse
    {
        if ($categoria->items()->exists()) {
            return redirect()->route('categorias.index')
                ->with('error', 'No se puede eliminar la categoría porque tiene items asociados.');
        }

        return $this->ejecutarAccion(function () use ($categoria): void {
            $categoria->delete();

            LoggerService::info('Categoría eliminada', [
                'nombre' => $categoria->nombre,
            ]);
        }, 'categorias.index', 'Categoría eliminada exitosamente.', 'No se pudo eliminar la categoría.', 'Error al eliminar categoría', [
            'id' => $categoria->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarNombre(Request $request, ?int $ignoreId = null): array
    {
        return $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:categorias,nombre'.($ignoreId ? ','.$ignoreId : ''),
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'categorias.index');
    }
}
