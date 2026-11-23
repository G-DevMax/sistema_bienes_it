<?php

namespace App\Http\Controllers;

use App\Models\dependencias;
use App\Models\funcionarios;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(Request $request)
    {
        $this->validateWithIndexRedirect($request, [
            'nombre' => 'required|string|max:120',
            'dependencia_id' => 'required|exists:dependencias,id',
        ], [
            'nombre.required' => 'El nombre del funcionario es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'dependencia_id.required' => 'Debe seleccionar una dependencia.',
            'dependencia_id.exists' => 'La dependencia seleccionada no es válida.',
        ], 'funcionarios.index');

        try {
            funcionarios::create([
                'nombre' => Str::title($request->nombre),
                'dependencia_id' => $request->dependencia_id,
                'estado_funcionario' => 1,
            ]);

            LoggerService::info('Funcionario registrado', [
                'nombre' => $request->nombre,
                'dependencia_id' => $request->dependencia_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar funcionario', [
                'nombre' => $request->nombre,
                'dependencia_id' => $request->dependencia_id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('funcionarios.index')->with('error', $this->mensajeError($e, 'No se pudo registrar el funcionario.'));
        }

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
        $this->validateWithIndexRedirect($request, [
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
        ], 'funcionarios.index');

        try {
            $funcionario = funcionarios::findOrFail($id);

            if ((int) $request->estado_funcionario === 0 && $funcionario->tieneEntregasPendientes()) {
                return redirect()->route('funcionarios.index')
                    ->with('error', funcionarios::MENSAJE_PENDIENTES)
                    ->with('advertencia_pendientes', funcionarios::MENSAJE_PENDIENTES);
            }

            $funcionario->update($request->only('nombre', 'dependencia_id', 'estado_funcionario'));

            LoggerService::info('Funcionario actualizado', [
                'nombre' => $request->nombre,
                'dependencia_id' => $request->dependencia_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar funcionario', [
                'nombre' => $request->nombre,
                'dependencia_id' => $request->dependencia_id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('funcionarios.index')->with('error', $this->mensajeError($e, 'No se pudo actualizar el funcionario.'));
        }

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        try {
            $funcionario = funcionarios::findOrFail($id);

            if ($funcionario->tieneEntregasPendientes()) {
                return redirect()->route('funcionarios.index')
                    ->with('error', funcionarios::MENSAJE_PENDIENTES)
                    ->with('advertencia_pendientes', funcionarios::MENSAJE_PENDIENTES);
            }

            $funcionario->estado_funcionario = 0;
            $funcionario->save();

            LoggerService::info('Funcionario dado de baja', [
                'nombre' => $funcionario->nombre,
                'dependencia_id' => $funcionario->dependencia_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al dar de baja funcionario', [
                'id' => $id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('funcionarios.index')->with('error', $this->mensajeError($e, 'No se pudo dar de baja al funcionario.'));
        }

        return redirect()->route('funcionarios.index')->with('success', 'Funcionario dado de baja exitosamente.');
    }
}
