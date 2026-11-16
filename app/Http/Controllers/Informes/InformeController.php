<?php

namespace App\Http\Controllers\Informes;

use App\Http\Controllers\Controller;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use App\Services\Informes\InformePdfService;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        $query = informes::with(['funcionario', 'detalle'])
            ->buscadorUniversal($request->search)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        $informes = $query->paginate(10)->withQueryString();
        $tiposProblema = informes::TIPOS_PROBLEMA;

        return view('Informes.index', compact('informes', 'tiposProblema'));
    }

    public function create()
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $tiposProblema = informes::TIPOS_PROBLEMA;
        $detalles = detalles_problemas::orderBy('detalle')->get();
        $fechaHora = now();

        return view('Informes.create', compact('funcionarios', 'tiposProblema', 'detalles', 'fechaHora'));
    }

    public function store(Request $request)
    {
        $datos = $this->validarInforme($request);

        try {
            $informe = informes::create($datos);

            LoggerService::info('Informe registrado', [
                'informe_id' => $informe->id,
                'funcionario_id' => $informe->funcionario_id,
                'tipo_problema' => $informe->tipo_problema,
                'detalle_id' => $informe->detalle_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar informe', [
                'funcionario_id' => $request->funcionario_id,
                'tipo_problema' => $request->tipo_problema,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('informes.index')->with('error', 'Error al registrar el informe: '.$e->getMessage());
        }

        return redirect()->route('informes.index')->with('success', 'Informe registrado exitosamente.');
    }

    public function show(string $id)
    {
        $informe = informes::with(['funcionario.dependencia', 'detalle'])->findOrFail($id);

        return view('Informes.show', compact('informe'));
    }

    public function exportarPdf(string $id, InformePdfService $pdfService)
    {
        $informe = informes::with(['funcionario.dependencia', 'detalle'])->findOrFail($id);
        $pdf = $pdfService->generar($informe, Auth::user()?->name);

        return $pdf->stream($pdfService->nombreArchivo($informe));
    }

    public function edit(string $id)
    {
        $informe = informes::findOrFail($id);
        $funcionarios = funcionarios::where('estado_funcionario', 1)
            ->orWhere('id', $informe->funcionario_id)
            ->orderBy('nombre')
            ->get();
        $tiposProblema = informes::TIPOS_PROBLEMA;
        $detalles = detalles_problemas::orderBy('detalle')->get();
        $fechaHora = $informe->created_at;

        return view('Informes.edit', compact('informe', 'funcionarios', 'tiposProblema', 'detalles', 'fechaHora'));
    }

    public function update(Request $request, string $id)
    {
        $informe = informes::findOrFail($id);
        $datos = $this->validarInforme($request);

        try {
            $informe->update($datos);

            LoggerService::info('Informe actualizado', [
                'informe_id' => $informe->id,
                'funcionario_id' => $informe->funcionario_id,
                'tipo_problema' => $informe->tipo_problema,
                'detalle_id' => $informe->detalle_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar informe', [
                'informe_id' => $informe->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('informes.index')->with('error', 'Error al actualizar el informe: '.$e->getMessage());
        }

        return redirect()->route('informes.index')->with('success', 'Informe actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        try {
            $informe = informes::findOrFail($id);
            $informe->delete();

            LoggerService::info('Informe eliminado', [
                'informe_id' => $id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al eliminar informe', [
                'informe_id' => $id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('informes.index')->with('error', 'Error al eliminar el informe: '.$e->getMessage());
        }

        return redirect()->route('informes.index')->with('success', 'Informe eliminado exitosamente.');
    }

    private function validarInforme(Request $request): array
    {
        // "Otro (especificar)" puede llegar como "" o "otro" desde el select del frontend
        $detalleId = $request->input('detalle_id');
        if ($detalleId === '' || $detalleId === 'otro') {
            $request->merge(['detalle_id' => null]);
            $detalleId = null;
        }

        return $this->validateWithIndexRedirect($request, [
            'titulo' => [
                'required',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('El título no puede superar las 100 palabras.');
                    }
                },
            ],
            'funcionario_id' => ['required', 'exists:funcionarios,id'],
            'tipo_problema' => ['required', Rule::in(informes::TIPOS_PROBLEMA)],
            'detalle_id' => [
                'nullable',
                Rule::exists('detalles_problemas', 'id')->where('tipo_problema', $request->input('tipo_problema')),
            ],
            'observacion' => [Rule::requiredIf(empty($detalleId)), 'nullable', 'string'],
        ], [
            'titulo.required' => 'El título del informe es obligatorio.',
            'titulo.max' => 'El título no puede superar los 500 caracteres.',
            'funcionario_id.required' => 'Debe seleccionar el funcionario atendido.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido.',
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'detalle_id.exists' => 'El detalle seleccionado no corresponde al tipo de problema indicado.',
            'observacion.required' => 'Debe especificar el problema y la solución en observaciones al seleccionar "Otro (especificar)".',
        ], 'informes.index');
    }
}
