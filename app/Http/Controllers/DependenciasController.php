<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DependenciasController extends Controller
{
    public function index(Request $request)
    {
        $dependencias = dependencias::buscadorUniversal($request->search)
            ->withCount('funcionarios')
            ->orderBy('nombre', 'ASC')
            ->paginate(10)
            ->withQueryString();

        return view('dependencias.index', compact('dependencias'));
    }

    public function create()
    {
        return view('dependencias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarNombre($request);

        return $this->ejecutarAccion(function () use ($datos): void {
            dependencias::create([
                'nombre' => Str::title($datos['nombre']),
            ]);

            LoggerService::info('Dependencia creada', [
                'nombre' => $datos['nombre'],
            ]);
        }, 'dependencias.index', 'Dependencia creada exitosamente.', 'No se pudo crear la dependencia.', 'Error al crear dependencia', [
            'nombre' => $request->nombre,
        ]);
    }

    public function edit(dependencias $dependencia)
    {
        return view('dependencias.edit', compact('dependencia'));
    }

    public function update(Request $request, dependencias $dependencia): RedirectResponse
    {
        $datos = $this->validarNombre($request, $dependencia->id);

        return $this->ejecutarAccion(function () use ($dependencia, $datos): void {
            $dependencia->update([
                'nombre' => Str::title($datos['nombre']),
            ]);

            LoggerService::info('Dependencia actualizada', [
                'nombre' => $datos['nombre'],
            ]);
        }, 'dependencias.index', 'Dependencia actualizada exitosamente.', 'No se pudo actualizar la dependencia.', 'Error al actualizar dependencia', [
            'nombre' => $request->nombre,
        ]);
    }

    public function destroy(dependencias $dependencia): RedirectResponse
    {
        if ($dependencia->funcionarios()->exists()) {
            return redirect()->route('dependencias.index')
                ->with('error', 'No se puede eliminar la dependencia porque tiene funcionarios asignados.');
        }

        return $this->ejecutarAccion(function () use ($dependencia): void {
            $dependencia->delete();

            LoggerService::info('Dependencia eliminada', [
                'nombre' => $dependencia->nombre,
            ]);
        }, 'dependencias.index', 'Dependencia eliminada exitosamente.', 'No se pudo eliminar la dependencia.', 'Error al eliminar dependencia', [
            'id' => $dependencia->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarNombre(Request $request, ?int $ignoreId = null): array
    {
        return $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120|unique:dependencias,nombre'.($ignoreId ? ','.$ignoreId : ''),
        ], [
            'nombre.required' => 'El nombre de la dependencia es obligatorio.',
            'nombre.unique' => 'Ya existe una dependencia con este nombre.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
        ], 'dependencias.index');
    }
}
