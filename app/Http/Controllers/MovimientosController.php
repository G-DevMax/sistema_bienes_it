<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\estados;
use App\Models\funcionarios;
use App\Models\items;
use App\Models\movimientos;
use App\Services\MovimientoService;
use App\Services\PlanillaMovimientoService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MovimientosController extends Controller
{
    public function __construct(private MovimientoService $movimientoService)
    {
    }

    public function index(Request $request)
    {
        $query = movimientos::with(['item', 'funcionario'])
            ->withSum(['hijos as hijos_activos' => fn ($q) => $q->whereNull('anulado_at')], 'cantidad')
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_movimiento', $tipo);
        }

        // solo prestamos/transferencias que aun tienen unidades sin devolver
        if ($request->boolean('pendientes')) {
            $query->whereNull('anulado_at')
                ->whereIn('tipo_movimiento', [movimientos::PRESTAMO, movimientos::TRANSFERENCIA])
                ->whereRaw('cantidad > (select coalesce(sum(h.cantidad), 0) from movimientos h where h.movimiento_origen_id = movimientos.id and h.anulado_at is null)');
        }

        $movimientos = $query->paginate(15)->withQueryString();

        return view('movimientos.index', compact('movimientos'));
    }

    public function create(Request $request)
    {
        // items con al menos una unidad en Informatica
        $items = items::where('stock_disponible', '>', 0)
            ->where(fn ($q) => $q->whereNull('codigo_patrimonial')->orWhere('estado_id', estados::DISPONIBLE))
            ->orderBy('descripcion')
            ->get();
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();

        $tipoSeleccionado = $request->query('tipo', movimientos::PRESTAMO);
        $itemSeleccionado = $request->query('item_id');

        return view('movimientos.create', compact('items', 'funcionarios', 'tipoSeleccionado', 'itemSeleccionado'));
    }

    public function store(Request $request)
    {
        $tipo = $request->input('tipo_movimiento');

        $datos = $request->validate([
            'tipo_movimiento' => ['required', Rule::in([movimientos::PRESTAMO, movimientos::TRANSFERENCIA, movimientos::BAJA])],
            'item_id' => ['required', 'exists:items,id'],
            'funcionario_id' => [Rule::requiredIf($tipo !== movimientos::BAJA), 'nullable', 'exists:funcionarios,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha_movimiento' => ['required', 'date'],
            'fecha_devolucion_prevista' => [Rule::requiredIf($tipo === movimientos::PRESTAMO), 'nullable', 'date', 'after_or_equal:' . substr((string) $request->input('fecha_movimiento'), 0, 10)],
            'motivo' => [Rule::requiredIf($tipo === movimientos::BAJA), 'nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'tipo_movimiento.required' => 'Debe seleccionar el tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento seleccionado no es válido.',
            'item_id.required' => 'Debe seleccionar un bien o item.',
            'item_id.exists' => 'El item seleccionado no es válido.',
            'funcionario_id.required' => 'Seleccione el funcionario que recibe el item.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido.',
            'cantidad.required' => 'Debe indicar la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos :min.',
            'fecha_movimiento.required' => 'La fecha de movimiento es obligatoria.',
            'fecha_movimiento.date' => 'La fecha de movimiento debe ser una fecha válida.',
            'fecha_devolucion_prevista.required' => 'Los préstamos requieren una fecha prevista de devolución.',
            'fecha_devolucion_prevista.after_or_equal' => 'La fecha prevista de devolución no puede ser anterior a la fecha del préstamo.',
            'motivo.required' => 'Indique el motivo de la baja.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ]);

        $mov = $tipo === movimientos::BAJA
            ? $this->movimientoService->registrarBaja($datos, Auth::id())
            : $this->movimientoService->registrarEntrega($datos, Auth::id());

        return redirect()->route('movimientos.show', $mov->id)->with('success', 'Movimiento registrado correctamente.');
    }

    public function show(string $id)
    {
        $movimiento = movimientos::with([
            'item.estado', 'funcionario', 'usuario', 'anuladoPor', 'origen.funcionario',
            'hijos' => fn ($q) => $q->with(['usuario', 'anuladoPor'])->orderBy('fecha_movimiento'),
        ])->findOrFail($id);

        return view('movimientos.show', compact('movimiento'));
    }

    // descarga la planilla FC 11 (Excel) con los datos del movimiento
    public function exportarPlanilla(string $id, PlanillaMovimientoService $planilla)
    {
        $movimiento = movimientos::findOrFail($id);

        if ($movimiento->estaAnulado()) {
            return redirect()->route('movimientos.show', $id)->with('error', 'No se puede imprimir la planilla de un movimiento anulado.');
        }

        $spreadsheet = $planilla->generar($movimiento);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $planilla->nombreArchivo($movimiento), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // devolucion (total o parcial) de un prestamo/transferencia
    public function devolver(Request $request, string $id)
    {
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha_movimiento' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'cantidad.required' => 'Debe indicar la cantidad a devolver.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad a devolver debe ser al menos :min.',
            'fecha_movimiento.required' => 'La fecha de devolución es obligatoria.',
            'fecha_movimiento.date' => 'La fecha de devolución debe ser una fecha válida.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ]);

        $this->movimientoService->registrarDevolucion((int) $id, $datos, Auth::id());

        return redirect()->route('movimientos.show', $id)->with('success', 'Devolución registrada; el stock fue actualizado.');
    }

    // baja de unidades que estan en manos del funcionario
    public function bajaEntrega(Request $request, string $id)
    {
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha_movimiento' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:255'],
            'observaciones' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'cantidad.required' => 'Debe indicar la cantidad a dar de baja.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos :min.',
            'fecha_movimiento.required' => 'La fecha de baja es obligatoria.',
            'fecha_movimiento.date' => 'La fecha debe ser una fecha válida.',
            'motivo.required' => 'Indique el motivo de la baja.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'observaciones.required' => 'Explique qué ocurrió y quién es responsable de la pérdida o daño.',
            'observaciones.min' => 'La nota de responsabilidad debe ser más descriptiva (mínimo 10 caracteres).',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ]);

        $this->movimientoService->registrarBajaDesdeEntrega((int) $id, $datos, Auth::id());

        return redirect()->route('movimientos.show', $id)->with('success', 'Baja registrada con nota de responsabilidad.');
    }

    public function anular(Request $request, string $id)
    {
        $datos = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'max:255'],
        ], [
            'motivo_anulacion.required' => 'Indique el motivo de la anulación.',
            'motivo_anulacion.max' => 'El motivo de anulación no puede superar los 255 caracteres.',
        ]);

        $this->movimientoService->anular((int) $id, $datos['motivo_anulacion'], Auth::id());

        return redirect()->route('movimientos.show', $id)->with('success', 'Movimiento anulado y stock revertido.');
    }
}
