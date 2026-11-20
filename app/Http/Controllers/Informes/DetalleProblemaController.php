<?php

namespace App\Http\Controllers\Informes;

use App\Http\Controllers\Controller;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DetalleProblemaController extends Controller
{
    public function index(Request $request)
    {
        $query = detalles_problemas::buscadorUniversal($request->search)
            ->orderBy('tipo_problema')
            ->orderBy('detalle');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        $detalles = $query->paginate(10)->withQueryString();
        $tiposProblema = informes::TIPOS_PROBLEMA;

        return view('Informes.detalles_problemas.index', compact('detalles', 'tiposProblema'));
    }

    public function create()
    {
        $tiposProblema = informes::TIPOS_PROBLEMA;

        return view('Informes.detalles_problemas.create', compact('tiposProblema'));
    }

    public function store(Request $request)
    {
        $datos = $this->validarDetalle($request);

        try {
            $detalle = detalles_problemas::create($datos);

            LoggerService::info('Detalle de problema registrado', [
                'detalle_id' => $detalle->id,
                'tipo_problema' => $detalle->tipo_problema,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar detalle de problema', [
                'tipo_problema' => $request->tipo_problema,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-problemas.index')
                ->with('error', $this->mensajeError($e, 'No se pudo registrar el detalle de problema.'));
        }

        return redirect()->route('detalles-problemas.index')
            ->with('success', 'Detalle de problema registrado exitosamente.');
    }

    public function show(string $id)
    {
        $detalle = detalles_problemas::withCount(['informes', 'tickets'])->findOrFail($id);

        return view('Informes.detalles_problemas.show', compact('detalle'));
    }

    public function edit(string $id)
    {
        $detalle = detalles_problemas::findOrFail($id);
        $tiposProblema = informes::TIPOS_PROBLEMA;

        return view('Informes.detalles_problemas.edit', compact('detalle', 'tiposProblema'));
    }

    public function update(Request $request, string $id)
    {
        $detalle = detalles_problemas::findOrFail($id);
        $datos = $this->validarDetalle($request, $detalle->id);

        try {
            $detalle->update($datos);

            LoggerService::info('Detalle de problema actualizado', [
                'detalle_id' => $detalle->id,
                'tipo_problema' => $detalle->tipo_problema,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar detalle de problema', [
                'detalle_id' => $detalle->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-problemas.index')
                ->with('error', $this->mensajeError($e, 'No se pudo actualizar el detalle de problema.'));
        }

        return redirect()->route('detalles-problemas.index')
            ->with('success', 'Detalle de problema actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        $detalle = detalles_problemas::findOrFail($id);

        if ($detalle->informes()->exists() || $detalle->tickets()->exists()) {
            return redirect()->route('detalles-problemas.index')
                ->with('error', 'No se puede eliminar el detalle porque está siendo utilizado en informes o tickets.');
        }

        try {
            $detalle->delete();

            LoggerService::info('Detalle de problema eliminado', [
                'detalle_id' => $id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al eliminar detalle de problema', [
                'detalle_id' => $id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('detalles-problemas.index')
                ->with('error', $this->mensajeError($e, 'No se pudo eliminar el detalle de problema.'));
        }

        return redirect()->route('detalles-problemas.index')
            ->with('success', 'Detalle de problema eliminado exitosamente.');
    }

    private function validarDetalle(Request $request, ?int $ignoreId = null): array
    {
        return $this->validateWithIndexRedirect($request, [
            'tipo_problema' => ['required', Rule::in(informes::TIPOS_PROBLEMA)],
            'detalle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('detalles_problemas', 'detalle')
                    ->where('tipo_problema', $request->input('tipo_problema'))
                    ->ignore($ignoreId),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('El detalle del problema no puede superar las 100 palabras.');
                    }
                },
            ],
        ], [
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'detalle.required' => 'El detalle del problema es obligatorio.',
            'detalle.max' => 'El detalle no puede superar los 255 caracteres.',
            'detalle.unique' => 'Ya existe ese detalle registrado para el tipo de problema seleccionado.',
        ], 'detalles-problemas.index');
    }
}
