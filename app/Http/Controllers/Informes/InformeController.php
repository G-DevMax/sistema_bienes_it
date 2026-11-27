<?php

namespace App\Http\Controllers\Informes;

use App\Enums\TipoProblema;
use App\Http\Controllers\Controller;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\detalles_soluciones;
use App\Models\Informes\informes;
use App\Services\Informes\InformePdfService;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        $query = informes::with(['funcionario', 'detalle', 'solucion', 'tecnico'])
            ->buscadorUniversal($request->search)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        $informes = $query->paginate(10)->withQueryString();
        $tiposProblema = TipoProblema::values();

        return view('Informes.index', compact('informes', 'tiposProblema'));
    }

    public function create()
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $fechaHora = now();

        return view('Informes.create', array_merge(
            compact('funcionarios', 'fechaHora'),
            $this->catalogosFormulario()
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarInforme($request);
        $datos['user_id'] = Auth::id();

        return $this->ejecutarAccion(function () use ($datos): void {
            $informe = informes::create($datos);

            LoggerService::info('Informe registrado', [
                'informe_id' => $informe->id,
                'funcionario_id' => $informe->funcionario_id,
                'tipo_problema' => $informe->tipo_problema,
                'detalle_id' => $informe->detalle_id,
                'solucion_id' => $informe->solucion_id,
            ]);
        }, 'informes.index', 'Informe registrado exitosamente.', 'No se pudo registrar el informe.', 'Error al registrar informe', [
            'funcionario_id' => $request->funcionario_id,
            'tipo_problema' => $request->tipo_problema,
        ]);
    }

    public function show(string $id)
    {
        $informe = informes::with(['funcionario.dependencia', 'detalle', 'solucion', 'tecnico', 'ticket'])->findOrFail($id);

        return view('Informes.show', compact('informe'));
    }

    public function exportarPdf(string $id, InformePdfService $pdfService)
    {
        $informe = informes::with(['funcionario.dependencia', 'detalle', 'solucion', 'tecnico', 'ticket'])->findOrFail($id);
        $pdf = $pdfService->generar($informe);

        return $pdf->stream($pdfService->nombreArchivo($informe));
    }

    public function edit(string $id)
    {
        $informe = informes::with('ticket')->findOrFail($id);
        $funcionarios = funcionarios::where('estado_funcionario', 1)
            ->orWhere('id', $informe->funcionario_id)
            ->orderBy('nombre')
            ->get();
        $fechaHora = $informe->created_at;

        return view('Informes.edit', array_merge(
            compact('informe', 'funcionarios', 'fechaHora'),
            $this->catalogosFormulario()
        ));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $informe = informes::findOrFail($id);

        if ($informe->vieneDeTicket() && (
            ($request->has('funcionario_id') && (int) $request->input('funcionario_id') !== (int) $informe->funcionario_id)
            || ($request->has('tipo_problema') && $request->input('tipo_problema') !== $informe->tipo_problema)
        )) {
            return redirect()->route('informes.edit', $informe->id)
                ->with('error', 'No se puede modificar el funcionario ni el tipo de problema de un informe generado desde un ticket.');
        }

        $datos = $this->validarInforme($request, $informe);

        return $this->ejecutarAccion(function () use ($informe, $datos): void {
            $informe->update($datos);

            LoggerService::info('Informe actualizado', [
                'informe_id' => $informe->id,
                'funcionario_id' => $informe->funcionario_id,
                'tipo_problema' => $informe->tipo_problema,
                'detalle_id' => $informe->detalle_id,
                'solucion_id' => $informe->solucion_id,
            ]);
        }, 'informes.index', 'Informe actualizado exitosamente.', 'No se pudo actualizar el informe.', 'Error al actualizar informe', [
            'informe_id' => $informe->id,
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $informe = informes::findOrFail($id);

        if ($informe->vieneDeTicket()) {
            return redirect()->route('informes.index')
                ->with('error', 'No se puede eliminar un informe vinculado a un ticket.');
        }

        return $this->ejecutarAccion(function () use ($informe, $id): void {
            $informe->delete();

            LoggerService::info('Informe eliminado', [
                'informe_id' => $id,
            ]);
        }, 'informes.index', 'Informe eliminado exitosamente.', 'No se pudo eliminar el informe.', 'Error al eliminar informe', [
            'informe_id' => $id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogosFormulario(): array
    {
        return [
            'tiposProblema' => TipoProblema::values(),
            'detalles' => detalles_problemas::orderBy('detalle')->get(),
            'soluciones' => detalles_soluciones::orderBy('solucion')->get(),
        ];
    }

    private function validarInforme(Request $request, ?informes $informe = null): array
    {
        // "Otro (especificar)" puede llegar como "" o "otro" desde los selects del frontend
        foreach (['detalle_id', 'solucion_id'] as $campo) {
            $valor = $request->input($campo);
            if ($valor === '' || $valor === 'otro') {
                $request->merge([$campo => null]);
            }
        }

        $vieneDeTicket = $informe?->vieneDeTicket() ?? false;
        $tipoProblema = $vieneDeTicket ? $informe->tipo_problema : $request->input('tipo_problema');

        $reglas = [
            'titulo' => [
                'required',
                'string',
                'max:500',
                $this->reglaMaxPalabras(100, 'El título no puede superar las 100 palabras.'),
            ],
            'detalle_id' => [
                'nullable',
                Rule::exists('detalles_problemas', 'id')->where('tipo_problema', $tipoProblema),
            ],
            'solucion_id' => [
                'nullable',
                Rule::exists('detalles_soluciones', 'id')->where('tipo_problema', $tipoProblema),
            ],
            'observacion' => [
                Rule::requiredIf(empty($request->input('detalle_id')) && empty($request->input('solucion_id'))),
                'nullable',
                'string',
            ],
        ];

        if (! $vieneDeTicket) {
            $reglas['funcionario_id'] = ['required', 'exists:funcionarios,id'];
            $reglas['tipo_problema'] = ['required', Rule::enum(TipoProblema::class)];
        }

        return $this->validateWithIndexRedirect($request, $reglas, [
            'titulo.required' => 'El título del informe es obligatorio.',
            'titulo.max' => 'El título no puede superar los 500 caracteres.',
            'funcionario_id.required' => 'Debe seleccionar el funcionario atendido.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido.',
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.enum' => 'El tipo de problema seleccionado no es válido.',
            'detalle_id.exists' => 'El detalle seleccionado no corresponde al tipo de problema indicado.',
            'solucion_id.exists' => 'La solución común seleccionada no corresponde al tipo de problema indicado.',
            'observacion.required' => 'Debe especificar el problema y la solución en observaciones al seleccionar "Otro (especificar)".',
        ], 'informes.index');
    }
}
