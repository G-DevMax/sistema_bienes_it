<?php

namespace App\Http\Controllers\Informes;

use App\Enums\TipoProblema;
use App\Http\Controllers\Controller;
use App\Models\Informes\detalles_soluciones;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DetalleSolucionController extends Controller
{
    public function index(Request $request)
    {
        $query = detalles_soluciones::buscadorUniversal($request->search)
            ->orderBy('tipo_problema')
            ->orderBy('solucion');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        $soluciones = $query->paginate(10)->withQueryString();
        $tiposProblema = TipoProblema::values();

        return view('Informes.detalles_soluciones.index', compact('soluciones', 'tiposProblema'));
    }

    public function create()
    {
        $tiposProblema = TipoProblema::values();

        return view('Informes.detalles_soluciones.create', compact('tiposProblema'));
    }

    public function store(Request $request)
    {
        $datos = $this->validarSolucion($request);

        try {
            $solucion = detalles_soluciones::create($datos);

            LoggerService::info('Solución común registrada', [
                'solucion_id' => $solucion->id,
                'tipo_problema' => $solucion->tipo_problema,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar solución común', [
                'tipo_problema' => $request->tipo_problema,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-soluciones.index')
                ->with('error', $this->mensajeError($e, 'No se pudo registrar la solución común.'));
        }

        return redirect()->route('detalles-soluciones.index')
            ->with('success', 'Solución común registrada exitosamente.');
    }

    public function show(string $id)
    {
        $solucion = detalles_soluciones::withCount(['informes', 'tickets'])->findOrFail($id);

        return view('Informes.detalles_soluciones.show', compact('solucion'));
    }

    public function edit(string $id)
    {
        $solucion = detalles_soluciones::findOrFail($id);
        $tiposProblema = TipoProblema::values();

        return view('Informes.detalles_soluciones.edit', compact('solucion', 'tiposProblema'));
    }

    public function update(Request $request, string $id)
    {
        $solucion = detalles_soluciones::findOrFail($id);
        $datos = $this->validarSolucion($request, $solucion->id);

        try {
            $solucion->update($datos);

            LoggerService::info('Solución común actualizada', [
                'solucion_id' => $solucion->id,
                'tipo_problema' => $solucion->tipo_problema,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar solución común', [
                'solucion_id' => $solucion->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-soluciones.index')
                ->with('error', $this->mensajeError($e, 'No se pudo actualizar la solución común.'));
        }

        return redirect()->route('detalles-soluciones.index')
            ->with('success', 'Solución común actualizada exitosamente.');
    }

    public function destroy(string $id)
    {
        $solucion = detalles_soluciones::findOrFail($id);

        if ($solucion->informes()->exists() || $solucion->tickets()->exists()) {
            return redirect()->route('detalles-soluciones.index')
                ->with('error', 'No se puede eliminar la solución porque está siendo utilizada en informes o tickets.');
        }

        try {
            $solucion->delete();

            LoggerService::info('Solución común eliminada', [
                'solucion_id' => $id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al eliminar solución común', [
                'solucion_id' => $id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-soluciones.index')
                ->with('error', $this->mensajeError($e, 'No se pudo eliminar la solución común.'));
        }

        return redirect()->route('detalles-soluciones.index')
            ->with('success', 'Solución común eliminada exitosamente.');
    }

    private function validarSolucion(Request $request, ?int $ignoreId = null): array
    {
        return $this->validateWithIndexRedirect($request, [
            'tipo_problema' => ['required', Rule::enum(TipoProblema::class)],
            'solucion' => [
                'required',
                'string',
                'max:255',
                Rule::unique('detalles_soluciones', 'solucion')
                    ->where('tipo_problema', $request->input('tipo_problema'))
                    ->ignore($ignoreId),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('La solución común no puede superar las 100 palabras.');
                    }
                },
            ],
        ], [
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'solucion.required' => 'La descripción de la solución es obligatoria.',
            'solucion.max' => 'La solución no puede superar los 255 caracteres.',
            'solucion.unique' => 'Ya existe esa solución registrada para el tipo de problema seleccionado.',
        ], 'detalles-soluciones.index');
    }
}
