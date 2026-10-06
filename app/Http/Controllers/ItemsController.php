<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\categorias;
use App\Models\items;
use App\Services\MovimientoService;

class ItemsController extends Controller
{
    public function __construct(private MovimientoService $movimientoService)
    {
    }

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
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:300'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'tiene_codigo' => ['nullable', 'boolean'],
            'codigo_patrimonial' => ['required_if:tiene_codigo,1', 'nullable', 'string', 'max:60', 'unique:items,codigo_patrimonial'],
            'stock' => ['required_unless:tiene_codigo,1', 'nullable', 'integer', 'min:1'],
        ], [
            'codigo_patrimonial.required_if' => 'Ingresá el código patrimonial o desmarcá la opción "Tiene código patrimonial".',
            'codigo_patrimonial.unique' => 'Ya existe un item con ese código patrimonial.',
        ]);

        if (!$request->boolean('tiene_codigo')) {
            $datos['codigo_patrimonial'] = null;
        }

        $this->movimientoService->crearItem($datos, Auth::id());

        return redirect()->route('items.index')->with('success', "Item creado correctamente");
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
        $categorias = categorias::all();
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

        $datos = $request->validate($reglas);
        $item->update($datos);

        return redirect()->route('items.index')->with('success', "Item actualizado correctamente");
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
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->movimientoService->ingresarStock((int) $id, (int) $datos['cantidad'], Auth::id(), $datos['observaciones'] ?? null);

        return redirect()->route('items.index')->with('success', "Se ingresaron {$datos['cantidad']} unidad(es) al stock");
    }
}
