<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Models\funcionarios;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FuncionariosController extends Controller
{
    public function index(Request $request)
    {
        $funcionarios = funcionarios::buscadorUniversal($request->search)
            ->with('dependencia')
            ->withExists('entregasPendientes as tiene_pendientes')
            ->orderBy('nombre', 'ASC')
            ->paginate(10)
            ->withQueryString();

        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();

        return view('funcionarios.index', compact('funcionarios', 'dependencias'));
    }

    public function create()
    {
        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();

        return view('funcionarios.create', compact('dependencias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarFuncionario($request);

        return $this->ejecutarAccion(function () use ($datos): void {
            funcionarios::create([
                'nombre' => Str::title($datos['nombre']),
                'dependencia_id' => $datos['dependencia_id'],
                'estado_funcionario' => 1,
            ]);

            LoggerService::info('Funcionario registrado', [
                'nombre' => $datos['nombre'],
                'dependencia_id' => $datos['dependencia_id'],
            ]);
        }, 'funcionarios.index', 'Funcionario registrado exitosamente.', 'No se pudo registrar el funcionario.', 'Error al registrar funcionario', [
            'nombre' => $request->nombre,
            'dependencia_id' => $request->dependencia_id,
        ]);
    }

    public function edit(funcionarios $funcionario)
    {
        $dependencias = dependencias::orderBy('nombre', 'ASC')->get();

        return view('funcionarios.edit', [
            'funcionarios' => $funcionario,
            'dependencias' => $dependencias,
        ]);
    }

    public function update(Request $request, funcionarios $funcionario): RedirectResponse
    {
        $datos = $this->validarFuncionario($request, true);

        return $this->ejecutarAccion(function () use ($funcionario, $datos): ?RedirectResponse {
            if ((int) $datos['estado_funcionario'] === 0 && $funcionario->tieneEntregasPendientes()) {
                return $this->redirigirPendientes();
            }

            $funcionario->update($datos);

            LoggerService::info('Funcionario actualizado', [
                'nombre' => $datos['nombre'],
                'dependencia_id' => $datos['dependencia_id'],
            ]);

            return null;
        }, 'funcionarios.index', 'Funcionario actualizado exitosamente.', 'No se pudo actualizar el funcionario.', 'Error al actualizar funcionario', [
            'nombre' => $request->nombre,
            'dependencia_id' => $request->dependencia_id,
        ]);
    }

    public function destroy(funcionarios $funcionario): RedirectResponse
    {
        return $this->ejecutarAccion(function () use ($funcionario): ?RedirectResponse {
            if ($funcionario->tieneEntregasPendientes()) {
                return $this->redirigirPendientes();
            }

            $funcionario->update(['estado_funcionario' => 0]);

            LoggerService::info('Funcionario dado de baja', [
                'nombre' => $funcionario->nombre,
                'dependencia_id' => $funcionario->dependencia_id,
            ]);

            return null;
        }, 'funcionarios.index', 'Funcionario dado de baja exitosamente.', 'No se pudo dar de baja al funcionario.', 'Error al dar de baja funcionario', [
            'id' => $funcionario->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarFuncionario(Request $request, bool $incluirEstado = false): array
    {
        $reglas = [
            'nombre' => 'required|string|max:120',
            'dependencia_id' => 'required|exists:dependencias,id',
        ];

        if ($incluirEstado) {
            $reglas['estado_funcionario'] = 'required|in:0,1';
        }

        return $this->validateWithIndexRedirect($request, $reglas, [
            'nombre.required' => 'El nombre del funcionario es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'dependencia_id.required' => 'Debe seleccionar una dependencia.',
            'dependencia_id.exists' => 'La dependencia seleccionada no es válida.',
            'estado_funcionario.required' => 'Debe definir el estado del funcionario.',
            'estado_funcionario.in' => 'El estado seleccionado no es válido.',
        ], 'funcionarios.index');
    }

    private function redirigirPendientes(): RedirectResponse
    {
        return redirect()->route('funcionarios.index')
            ->with('error', funcionarios::MENSAJE_PENDIENTES)
            ->with('advertencia_pendientes', funcionarios::MENSAJE_PENDIENTES);
    }
}
