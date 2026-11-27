<?php

namespace App\Http\Controllers\Bienes;

use App\Http\Controllers\Controller;
use App\Models\Bienes\categorias;
use App\Models\Bienes\items;
use App\Services\Bienes\MovimientoService;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ItemsController extends Controller
{
    public function __construct(private MovimientoService $movimientoService) {}

    public function index(Request $request)
    {
        $items = items::with(['categoria', 'estado'])
            ->buscadorUniversal($request->search)
            ->orderBy('descripcion')
            ->paginate(10)
            ->withQueryString();
        // desglose de prestados / transferidos / inservibles por item (una consulta para toda la pagina)
        $resumen = $this->movimientoService->resumenPorItem($items->getCollection()->pluck('id'));

        return view('Bienes.items.index', compact('items', 'resumen'));
    }

    public function create()
    {
        $categorias = categorias::all();

        return view('Bienes.items.create', compact('categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarItem($request);

        if (! $request->boolean('tiene_codigo')) {
            $datos['codigo_patrimonial'] = null;
        }

        $lock = Cache::lock('crear_item_usuario_'.Auth::id(), 10);

        if (! $lock->get()) {
            return redirect()->route('items.index')->with('error', 'Ya se está procesando tu solicitud, porfavor espera....');
        }

        try {
            return $this->ejecutarAccion(function () use ($datos, $request): void {
                $this->movimientoService->crearItem($datos, Auth::id());

                LoggerService::info('Crear item', [
                    'descripcion' => $datos['descripcion'],
                    'categoria_id' => $datos['categoria_id'],
                    'tiene_codigo' => $request->tiene_codigo,
                    'codigo_patrimonial' => $datos['codigo_patrimonial'] ?? null,
                    'stock' => $datos['stock'] ?? null,
                ]);
            }, 'items.index', 'Item creado correctamente.', 'Error al crear item.', 'Error al crear item', [
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'tiene_codigo' => $request->tiene_codigo,
                'codigo_patrimonial' => $request->codigo_patrimonial,
                'stock' => $request->stock,
            ]);
        } finally {
            $lock->release();
        }
    }

    public function edit(items $item)
    {
        $categorias = categorias::orderBy('nombre', 'ASC')->get();

        return view('Bienes.items.edit', compact('categorias', 'item'));
    }

    /**
     * El stock y el estado NO se editan aqui: solo cambian mediante movimientos.
     */
    public function update(Request $request, items $item): RedirectResponse
    {
        $datos = $this->validarItem($request, $item);

        return $this->ejecutarAccion(function () use ($item, $datos): void {
            $item->update($datos);

            LoggerService::info('Item actualizado', [
                'descripcion' => $datos['descripcion'],
                'categoria_id' => $datos['categoria_id'],
                'codigo_patrimonial' => $datos['codigo_patrimonial'] ?? null,
            ]);
        }, 'items.index', 'Item actualizado correctamente.', 'Error al actualizar item.', 'Error al actualizar item', [
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'codigo_patrimonial' => $request->codigo_patrimonial,
        ]);
    }

    /**
     * Las bajas se registran como movimiento para mantener la trazabilidad.
     */
    public function destroy(string $id)
    {
        return redirect()->route('movimientos.create', ['tipo' => 'baja', 'item_id' => $id]);
    }

    /**
     * Ingreso de unidades adicionales para items genericos.
     */
    public function ingresarStock(Request $request, string $id): RedirectResponse
    {
        $datos = $this->validateWithIndexRedirect($request, [
            'cantidad' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'cantidad.required' => 'Debe indicar la cantidad de unidades a ingresar.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad a ingresar debe ser al menos :min.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
        ], 'items.index');

        $lock = Cache::lock('ingresar_stock_usuario_'.Auth::id(), 10);

        if (! $lock->get()) {
            return redirect()->route('items.index')->with('error', 'Ya se está procesando tu solicitud, porfavor espera....');
        }

        try {
            return $this->ejecutarAccion(function () use ($id, $datos): void {
                $this->movimientoService->ingresarStock((int) $id, (int) $datos['cantidad'], Auth::id(), $datos['observaciones'] ?? null);

                LoggerService::info('Stock ingresado', [
                    'cantidad' => $datos['cantidad'],
                    'observaciones' => $datos['observaciones'] ?? null,
                ]);
            }, 'items.index', "Se ingresaron {$datos['cantidad']} unidad(es) al stock", 'Error al ingresar stock.', 'Error al ingresar stock', [
                'cantidad' => $request->cantidad,
                'observaciones' => $request->observaciones,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validarItem(Request $request, ?items $item = null): array
    {
        $reglas = [
            'descripcion' => ['required', 'string', 'max:300'],
            'categoria_id' => ['required', 'exists:categorias,id'],
        ];

        if ($item === null) {
            $reglas['tiene_codigo'] = ['nullable', 'boolean'];
            $reglas['codigo_patrimonial'] = ['required_if:tiene_codigo,1', 'nullable', 'string', 'max:60', 'unique:items,codigo_patrimonial'];
            $reglas['stock'] = ['required_unless:tiene_codigo,1', 'nullable', 'integer', 'min:1'];
        } elseif ($item->esSerializado()) {
            $reglas['codigo_patrimonial'] = ['required', 'string', 'max:60', Rule::unique('items', 'codigo_patrimonial')->ignore($item->id)];
        }

        return $this->validateWithIndexRedirect($request, $reglas, [
            'descripcion.required' => 'La descripción del item es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 300 caracteres.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'codigo_patrimonial.required' => 'El código patrimonial es obligatorio para items serializados.',
            'codigo_patrimonial.required_if' => 'Ingresá el código patrimonial o desmarcá la opción "Tiene código patrimonial".',
            'codigo_patrimonial.unique' => 'Ya existe un item con ese código patrimonial.',
            'codigo_patrimonial.max' => 'El código patrimonial no puede superar los 60 caracteres.',
            'stock.required_unless' => 'El stock inicial es obligatorio para items genéricos.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock inicial debe ser al menos :min.',
        ], 'items.index');
    }
}
