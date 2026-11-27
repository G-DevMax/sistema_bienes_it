<?php

namespace App\Http\Controllers\Informes;

use App\Enums\TipoProblema;
use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

abstract class AbstractCatalogoProblemaController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelo;

    protected string $tabla;

    protected string $campoTexto;

    protected string $vistaPrefix;

    protected string $rutaPrefix;

    protected string $variableListado;

    protected string $variableItem;

    protected string $etiqueta;

    protected string $etiquetaError;

    protected string $articulo;

    protected string $terminacionGenero;

    protected string $mensajeRequired;

    protected string $mensajeMax;

    protected string $mensajeUnique;

    protected string $mensajePalabras;

    protected string $mensajeEnUso;

    public function index(Request $request)
    {
        $query = ($this->modelo)::buscadorUniversal($request->search)
            ->orderBy('tipo_problema')
            ->orderBy($this->campoTexto);

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        return view($this->vistaPrefix.'.index', [
            $this->variableListado => $query->paginate(10)->withQueryString(),
            'tiposProblema' => TipoProblema::values(),
        ]);
    }

    public function create()
    {
        return view($this->vistaPrefix.'.create', [
            'tiposProblema' => TipoProblema::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarRegistro($request);

        return $this->ejecutarAccion(function () use ($datos): void {
            $registro = ($this->modelo)::create($datos);

            LoggerService::info($this->etiqueta.' registrad'.$this->terminacionGenero, [
                $this->variableItem.'_id' => $registro->id,
                'tipo_problema' => $registro->tipo_problema,
            ]);
        }, $this->rutaPrefix.'.index',
            $this->etiqueta.' registrad'.$this->terminacionGenero.' exitosamente.',
            'No se pudo registrar '.$this->articulo.' '.$this->etiquetaError.'.',
            'Error al registrar '.$this->etiquetaError,
            ['tipo_problema' => $request->tipo_problema]
        );
    }

    public function show(string $id)
    {
        $registro = ($this->modelo)::withCount(['informes', 'tickets'])->findOrFail($id);

        return view($this->vistaPrefix.'.show', [
            $this->variableItem => $registro,
        ]);
    }

    public function edit(string $id)
    {
        $registro = ($this->modelo)::findOrFail($id);

        return view($this->vistaPrefix.'.edit', [
            $this->variableItem => $registro,
            'tiposProblema' => TipoProblema::values(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $registro = ($this->modelo)::findOrFail($id);
        $datos = $this->validarRegistro($request, $registro->id);

        return $this->ejecutarAccion(function () use ($registro, $datos): void {
            $registro->update($datos);

            LoggerService::info($this->etiqueta.' actualizad'.$this->terminacionGenero, [
                $this->variableItem.'_id' => $registro->id,
                'tipo_problema' => $registro->tipo_problema,
            ]);
        }, $this->rutaPrefix.'.index',
            $this->etiqueta.' actualizad'.$this->terminacionGenero.' exitosamente.',
            'No se pudo actualizar '.$this->articulo.' '.$this->etiquetaError.'.',
            'Error al actualizar '.$this->etiquetaError,
            [$this->variableItem.'_id' => $registro->id]
        );
    }

    public function destroy(string $id): RedirectResponse
    {
        $registro = ($this->modelo)::findOrFail($id);

        if ($registro->informes()->exists() || $registro->tickets()->exists()) {
            return redirect()->route($this->rutaPrefix.'.index')
                ->with('error', $this->mensajeEnUso);
        }

        return $this->ejecutarAccion(function () use ($registro, $id): void {
            $registro->delete();

            LoggerService::info($this->etiqueta.' eliminad'.$this->terminacionGenero, [
                $this->variableItem.'_id' => $id,
            ]);
        }, $this->rutaPrefix.'.index',
            $this->etiqueta.' eliminad'.$this->terminacionGenero.' exitosamente.',
            'No se pudo eliminar '.$this->articulo.' '.$this->etiquetaError.'.',
            'Error al eliminar '.$this->etiquetaError,
            [$this->variableItem.'_id' => $id]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validarRegistro(Request $request, ?int $ignoreId = null): array
    {
        return $this->validateWithIndexRedirect($request, [
            'tipo_problema' => ['required', Rule::enum(TipoProblema::class)],
            $this->campoTexto => [
                'required',
                'string',
                'max:255',
                Rule::unique($this->tabla, $this->campoTexto)
                    ->where('tipo_problema', $request->input('tipo_problema'))
                    ->ignore($ignoreId),
                $this->reglaMaxPalabras(100, $this->mensajePalabras),
            ],
        ], [
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.enum' => 'El tipo de problema seleccionado no es válido.',
            $this->campoTexto.'.required' => $this->mensajeRequired,
            $this->campoTexto.'.max' => $this->mensajeMax,
            $this->campoTexto.'.unique' => $this->mensajeUnique,
        ], $this->rutaPrefix.'.index');
    }
}
