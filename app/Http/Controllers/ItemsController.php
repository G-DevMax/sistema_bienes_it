<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\categorias;
use App\Models\items;
use App\Services\LoggerService;
use App\Services\MovimientoService;
use Illuminate\Support\Facades\Log;

class ItemsController extends Controller
{
    public function __construct(private MovimientoService $movimientoService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = items::with(['categoria', 'estado'])->orderBy('descripcion')->paginate(10);
        // desglose de prestados / transferidos / inservibles por item (una consulta para toda la pagina)
        $resumen = $this->movimientoService->resumenPorItem($items->getCollection()->pluck('id'));

        return view("items.index", compact('items', 'resumen'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = categorias::all();

        return view("items.create", compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $this->validateWithIndexRedirect($request, [
            'descripcion' => ['required', 'string', 'max:300'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'tiene_codigo' => ['nullable', 'boolean'],
            'codigo_patrimonial' => ['required_if:tiene_codigo,1', 'nullable', 'string', 'max:60', 'unique:items,codigo_patrimonial'],
            'stock' => ['required_unless:tiene_codigo,1', 'nullable', 'integer', 'min:1'],
        ], [
            'descripcion.required' => 'La descripción del item es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 300 caracteres.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'codigo_patrimonial.required_if' => 'Ingresá el código patrimonial o desmarcá la opción "Tiene código patrimonial".',
            'codigo_patrimonial.unique' => 'Ya existe un item con ese código patrimonial.',
            'codigo_patrimonial.max' => 'El código patrimonial no puede superar los 60 caracteres.',
            'stock.required_unless' => 'El stock inicial es obligatorio para items genéricos.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock inicial debe ser al menos :min.',
        ], 'items.index');

        if (!$request->boolean('tiene_codigo')) {
            $datos['codigo_patrimonial'] = null;
        }

        try {
            LoggerService::info('Crear item', [
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'tiene_codigo' => $request->tiene_codigo,
                'codigo_patrimonial' => $request->codigo_patrimonial,
                'stock' => $request->stock,
                'user_id' => Auth::id(),
            ]);

            $this->movimientoService->crearItem($datos, Auth::id());
        } catch (\Exception $e) {
            LoggerService::error('Error al crear item', [
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'tiene_codigo' => $request->tiene_codigo,
                'codigo_patrimonial' => $request->codigo_patrimonial,
                'stock' => $request->stock,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('items.index')->with('error', "Error al crear item.");
        }
        return redirect()->route('items.index')->with('success', "Item creado correctamente.");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('items.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $categorias = categorias::orderBy('nombre', 'ASC')->get();
        $item = items::findOrFail($id);

        return view("items.edit", compact("categorias", "item"));
    }

    /**
     * Update the specified resource in storage.
     * El stock y el estado NO se editan aqui: solo cambian mediante movimientos.
     */
    public function update(Request $request, string $id)
    {
        $item = items::findOrFail($id);

        $reglas = [
            'descripcion' => ['required', 'string', 'max:300'],
            'categoria_id' => ['required', 'exists:categorias,id'],
        ];
        if ($item->esSerializado()) {
            $reglas['codigo_patrimonial'] = ['required', 'string', 'max:60', Rule::unique('items', 'codigo_patrimonial')->ignore($item->id)];
        }

        $mensajes = [
            'descripcion.required' => 'La descripción del item es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 300 caracteres.',
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',
            'codigo_patrimonial.required' => 'El código patrimonial es obligatorio para items serializados.',
            'codigo_patrimonial.unique' => 'Ya existe un item con ese código patrimonial.',
            'codigo_patrimonial.max' => 'El código patrimonial no puede superar los 60 caracteres.',
        ];

        $datos = $this->validateWithIndexRedirect($request, $reglas, $mensajes, 'items.index');

        try {
            $item->update($datos);

            LoggerService::info('Item actualizado', [
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'codigo_patrimonial' => $request->codigo_patrimonial,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al actualizar item', [
                'descripcion' => $request->descripcion,
                'categoria_id' => $request->categoria_id,
                'codigo_patrimonial' => $request->codigo_patrimonial,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('items.index')->with('error', "Error al actualizar item.");
        }

        return redirect()->route('items.index')->with('success', "Item actualizado correctamente.");
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
    public function ingresarStock(Request $request, string $id)
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

        try {
            $this->movimientoService->ingresarStock((int) $id, (int) $datos['cantidad'], Auth::id(), $datos['observaciones'] ?? null);

            LoggerService::info('Stock ingresado', [
                'cantidad' => $request->cantidad,
                'observaciones' => $request->observaciones,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al ingresar stock', [
                'cantidad' => $request->cantidad,
                'observaciones' => $request->observaciones,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('items.index')->with('error', "Error al ingresar stock.");
        }

        return redirect()->route('items.index')->with('success', "Se ingresaron {$datos['cantidad']} unidad(es) al stock");
    }
}
