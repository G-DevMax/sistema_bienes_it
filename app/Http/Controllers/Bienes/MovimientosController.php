<?php

namespace App\Http\Controllers\Bienes;

use App\Http\Controllers\Controller;
use App\Models\Bienes\estados;
use App\Models\Bienes\items;
use App\Models\Bienes\movimientos;
use App\Models\funcionarios;
use App\Services\Bienes\InformeMovimientoService;
use App\Services\Bienes\MovimientoService;
use App\Services\Bienes\PlanillaMovimientoService;
use App\Services\LoggerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MovimientosController extends Controller
{
    private const PALETA_USUARIOS = [
        '#60a5fa',
        '#1CD6CE',
        '#a855f7',
        '#FEDB39',
        '#4ade80',
        '#f472b6',
        '#fb923c',
        '#818cf8',
    ];

    private const COLORES_TIPO = [
        movimientos::PRESTAMO => '#FEDB39',
        movimientos::TRANSFERENCIA => '#a855f7',
    ];

    public function __construct(private MovimientoService $movimientoService) {}

    public function dashboard(Request $request): View
    {
        $datosMes = $this->resolverMes($request);
        $vista = $request->query('vista') === 'usuarios' ? 'usuarios' : 'movimientos';
        $estadisticas = $this->calcularEstadisticas($datosMes['inicioMes'], $datosMes['finMes'], $vista);
        $resumenPrestamos = $this->contarPrestamosPorEstado($datosMes['inicioMes'], $datosMes['finMes']);

        return view('Bienes.menu_bientes', [
            'vista' => $vista,
            'filasTabla' => $estadisticas['filas'],
            'totalMes' => $estadisticas['totalMes'],
            'colores' => $estadisticas['colores'],
            'prestamosPendientes' => $resumenPrestamos['pendientes'],
            'prestamosCerrados' => $resumenPrestamos['cerrados'],
            'mes' => $datosMes['mes'],
            'nombreMes' => $datosMes['nombreMes'],
            'nombreMesSimple' => $datosMes['nombreMesSimple'],
            'anio' => $datosMes['anio'],
            'anioMinimo' => $datosMes['anioMinimo'],
            'anioMaximo' => $datosMes['anioMaximo'],
        ]);
    }

    public function estadisticasDatos(Request $request): JsonResponse
    {
        $datosMes = $this->resolverMes($request);
        $vista = $request->query('vista') === 'usuarios' ? 'usuarios' : 'movimientos';
        $estadisticas = $this->calcularEstadisticas($datosMes['inicioMes'], $datosMes['finMes'], $vista);

        return response()->json([
            'vista' => $vista,
            'mes' => $datosMes['mes'],
            'nombre_mes' => $datosMes['nombreMes'],
            'total_mes' => $estadisticas['totalMes'],
            'labels' => array_column($estadisticas['filas'], 'label'),
            'valores' => array_column($estadisticas['filas'], 'total'),
            'colores' => $estadisticas['colores'],
            'filas' => $estadisticas['filas'],
        ]);
    }

    public function index(Request $request)
    {
        $query = movimientos::with(['item', 'funcionario'])
            ->buscadorUniversal($request->search)
            ->withSum(['hijos as hijos_activos' => fn ($q) => $q->whereNull('anulado_at')], 'cantidad')
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_movimiento', $tipo);
        }

        // solo prestamos que aun tienen unidades sin devolver
        if ($request->boolean('pendientes')) {
            $query->whereNull('anulado_at')
                ->where('tipo_movimiento', movimientos::PRESTAMO)
                ->whereRaw('cantidad > (select coalesce(sum(h.cantidad), 0) from movimientos h where h.movimiento_origen_id = movimientos.id and h.anulado_at is null)');
        }

        $movimientos = $query->paginate(15)->withQueryString();

        // se trae a todos los funcionarios por orden alfabetico
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();

        return view('Bienes.movimientos.index', compact('movimientos', 'funcionarios'));
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
        $funcionarioSeleccionado = $request->query('funcionario_id');

        return view('Bienes.movimientos.create', compact('items', 'funcionarios', 'tipoSeleccionado', 'itemSeleccionado', 'funcionarioSeleccionado'));
    }

    public function store(Request $request)
    {
        $tipo = $request->input('tipo_movimiento');

        if ($tipo === movimientos::BAJA) {
            Gate::authorize('bienes.eliminar');
        }

        $datos = $this->validateWithIndexRedirect($request, [
            'tipo_movimiento' => ['required', Rule::in([movimientos::PRESTAMO, movimientos::TRANSFERENCIA, movimientos::BAJA])],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['required', 'distinct', 'exists:items,id'],
            'funcionario_id' => [Rule::requiredIf($tipo !== movimientos::BAJA), 'nullable', 'exists:funcionarios,id'],
            'cantidad' => ['required', 'array', 'min:1'],
            'cantidad.*' => ['required', 'integer', 'min:1'],
            'fecha_movimiento' => ['required', 'date'],
            'fecha_devolucion_prevista' => [Rule::requiredIf($tipo === movimientos::PRESTAMO), 'nullable', 'date', 'after_or_equal:'.substr((string) $request->input('fecha_movimiento'), 0, 10)],
            'motivo' => [Rule::requiredIf($tipo === movimientos::BAJA), 'nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ], [
            'tipo_movimiento.required' => 'Debe seleccionar el tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento seleccionado no es válido.',
            'item_id.required' => 'Debe seleccionar al menos un bien o item.',
            'item_id.*.exists' => 'El item seleccionado no es válido.',
            'item_id.*.distinct' => 'No se puede añadir el mismo item dos veces en un movimiento.',
            'funcionario_id.required' => 'Seleccione el funcionario que recibe el item.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido.',
            'cantidad.required' => 'Debe indicar la cantidad.',
            'cantidad.*.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.*.min' => 'La cantidad debe ser al menos :min.',
            'fecha_movimiento.required' => 'La fecha de movimiento es obligatoria.',
            'fecha_movimiento.date' => 'La fecha de movimiento debe ser una fecha válida.',
            'fecha_devolucion_prevista.required' => 'Los préstamos requieren una fecha prevista de devolución.',
            'fecha_devolucion_prevista.after_or_equal' => 'La fecha prevista de devolución no puede ser anterior a la fecha del préstamo.',
            'motivo.required' => 'Indique el motivo de la baja.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ], 'movimientos.index');

        try {
            $loteId = Str::uuid()->toString();
            $primerMovimientoId = null;

            DB::beginTransaction();

            foreach ($datos['item_id'] as $index => $itemId) {
                $cantidad = $datos['cantidad'][$index];

                $datosIndividuales = array_merge($datos, [
                    'item_id' => $itemId,
                    'cantidad' => $cantidad,
                ]);

                $mov = $tipo === movimientos::BAJA
                    ? $this->movimientoService->registrarBaja($datosIndividuales, Auth::id())
                    : $this->movimientoService->registrarEntrega($datosIndividuales, Auth::id());

                $mov->update(['lote_id' => $loteId]);

                if (! $primerMovimientoId) {
                    $primerMovimientoId = $mov->id;
                }

                LoggerService::info('Movimiento registrado', [
                    'id' => $mov->id,
                    'lote_id' => $loteId,
                    'tipo' => $mov->tipo_movimiento,
                    'item' => $mov->item_id,
                    'funcionario' => $mov->funcionario_id,
                    'cantidad' => $mov->cantidad,
                    'fecha' => $mov->fecha_movimiento,
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error('Error al registrar el movimiento', [
                'tipo' => $request->tipo_movimiento,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('movimientos.index')->with('error', $this->mensajeError($e, 'No se pudo registrar el movimiento.'));
        }

        return redirect()->route('movimientos.show', $primerMovimientoId)->with('success', 'Movimiento(s) registrado(s) correctamente.');
    }

    public function show(string $id)
    {
        $movimiento = movimientos::with([
            'item.estado',
            'funcionario',
            'usuario',
            'anuladoPor',
            'origen.funcionario',
            'hijos' => fn ($q) => $q->with(['usuario', 'anuladoPor'])->orderBy('fecha_movimiento'),
        ])->findOrFail($id);

        return view('Bienes.movimientos.show', compact('movimiento'));
    }

    // descarga la planilla FC 11 (Excel) con los datos del movimiento
    public function exportarPlanilla(string $id, PlanillaMovimientoService $planilla)
    {
        $movimiento = movimientos::findOrFail($id);

        if ($movimiento->estaAnulado()) {
            return redirect()->route('movimientos.show', $id)->with('error', 'No se puede imprimir la planilla de un movimiento anulado.');
        }

        $movimientos = $movimiento->lote_id
            ? movimientos::where('lote_id', $movimiento->lote_id)->orderBy('id')->get()
            : collect([$movimiento]);

        $spreadsheet = $planilla->generar($movimientos);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $planilla->nombreArchivo($movimientos), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // devolucion (total o parcial) de un prestamo/transferencia
    public function devolver(Request $request, string $id)
    {
        try {
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

            LoggerService::info('Devolución registrada', [
                'id' => $id,
                'cantidad' => $datos['cantidad'],
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'observaciones' => $datos['observaciones'],
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar la devolución', [
                'id' => $id,
                'cantidad' => $request->cantidad,
                'fecha_movimiento' => $request->fecha_movimiento,
                'observaciones' => $request->observaciones,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('movimientos.show', $id)->with('error', $this->mensajeError($e, 'No se pudo registrar la devolución.'));
        }

        return redirect()->route('movimientos.show', $id)->with('success', 'Devolución registrada; el stock fue actualizado.');
    }

    // baja de unidades que estan en manos del funcionario
    public function bajaEntrega(Request $request, string $id)
    {
        try {
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

            LoggerService::info('Baja desde entrega registrada', [
                'id' => $id,
                'cantidad' => $datos['cantidad'],
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'motivo' => $datos['motivo'],
                'observaciones' => $datos['observaciones'],
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al registrar la baja desde entrega', [
                'id' => $id,
                'cantidad' => $request->cantidad,
                'fecha_movimiento' => $request->fecha_movimiento,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('movimientos.show', $id)->with('error', $this->mensajeError($e, 'No se pudo registrar la baja desde entrega.'));
        }

        return redirect()->route('movimientos.show', $id)->with('success', 'Baja registrada con nota de responsabilidad.');
    }

    public function anular(Request $request, string $id)
    {
        try {

            $datos = $request->validate([
                'motivo_anulacion' => ['required', 'string', 'max:255'],
            ], [
                'motivo_anulacion.required' => 'Indique el motivo de la anulación.',
                'motivo_anulacion.max' => 'El motivo de anulación no puede superar los 255 caracteres.',
            ]);

            $this->movimientoService->anular((int) $id, $datos['motivo_anulacion'], Auth::id());

            LoggerService::info('Movimiento anulado', [
                'id' => $id,
                'motivo_anulacion' => $datos['motivo_anulacion'],
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('movimientos.show', $id)->with('success', 'Movimiento anulado y stock revertido.');
        } catch (\Exception $e) {
            LoggerService::error('Error al anular el movimiento', [
                'id' => $id,
                'motivo_anulacion' => $request->motivo_anulacion,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('movimientos.show', $id)->with('error', $this->mensajeError($e, 'No se pudo anular el movimiento.'));
        }
    }

    // funcion encargada de descargar en un informe los movimientos de insumos (perifericos/hardware)

    public function generarInforme(Request $request, InformeMovimientoService $informe)
    {
        $datos = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'tipo_movimiento' => ['required', Rule::in(['Prestamo', 'Transferencia'])],
            'funcionario' => ['required', Rule::when($request->funcionario !== 'todos', ['integer', 'exists:funcionarios,id'])],
        ], [
            'fecha_desde.required' => 'La fecha desde es obligatoria.',
            'fecha_desde.date' => 'La fecha desde debe ser una fecha válida.',
            'fecha_hasta.required' => 'La fecha hasta es obligatoria.',
            'fecha_hasta.date' => 'La fecha hasta debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser mayor o igual a la fecha desde.',
            'tipo_movimiento.required' => 'Debe seleccionar el tipo de movimientos a cargar en el informe.',
            'tipo_movimiento.in' => 'El tipo de movimiento seleccionado no es válido.',
            'funcionario.required' => "Debe seleccionar un funcionario o seleccionar 'Todos'",
            'funcionario.exists' => 'El funcionario seleccionado no es válido.',
            'funcionario.integer' => 'El funcionario seleccionado no es válido.',
        ]);

        $movimientos = $informe->obtenerMovimientos($datos['fecha_desde'], $datos['fecha_hasta'], $datos['tipo_movimiento'], $datos['funcionario']);

        $spreadsheet = $informe->generar($movimientos);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $informe->nombreArchivo($datos['tipo_movimiento'], $datos['fecha_desde'], $datos['fecha_hasta']), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Resuelve el mes seleccionado y sus límites en formato Y-m-d junto a etiquetas para la vista.
     *
     * @return array{mes: string, inicioMes: string, finMes: string, nombreMes: string, nombreMesSimple: string, anio: string, anioMinimo: int, anioMaximo: int}
     */
    private function resolverMes(Request $request): array
    {
        $rango = movimientos::query()
            ->whereNull('anulado_at')
            ->whereIn('tipo_movimiento', [movimientos::PRESTAMO, movimientos::TRANSFERENCIA])
            ->selectRaw('MIN(fecha_movimiento) as min_fecha, MAX(fecha_movimiento) as max_fecha')
            ->first();

        $anioBase = 2026;
        $anioActual = max(now()->year, $anioBase);
        $anioMinDb = (int) substr((string) ($rango->min_fecha ?? ''), 0, 4);
        $anioMaxDb = (int) substr((string) ($rango->max_fecha ?? ''), 0, 4);

        $anioMinimo = $anioMinDb >= $anioBase ? min($anioMinDb, $anioActual) : $anioBase;
        $anioMaximo = max($anioActual, $anioMaxDb, $anioMinimo);

        $mesParam = $request->query('mes');
        $limiteSuperior = max($anioMaximo, now()->addDay()->year);
        $mes = null;
        if (is_string($mesParam) && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mesParam, $coincidencias)) {
            $anioParam = (int) $coincidencias[1];
            if ($anioParam >= $anioMinimo && $anioParam <= $limiteSuperior) {
                $mes = $mesParam;
            }
        }
        $mes ??= sprintf('%04d-%02d', max(now()->year, $anioMinimo), now()->month);

        [$year, $month] = explode('-', $mes);
        $anioMaximo = max($anioMaximo, (int) $year);

        $inicio = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
        $inicioMes = $inicio->toDateString();
        $finMes = $inicio->copy()->endOfMonth()->toDateString();

        $meses = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];

        $nombreMesSimple = $meses[$month] ?? 'Mes';

        return [
            'mes' => $mes,
            'inicioMes' => $inicioMes,
            'finMes' => $finMes,
            'nombreMes' => $nombreMesSimple.' '.$year,
            'nombreMesSimple' => $nombreMesSimple,
            'anio' => $year,
            'anioMinimo' => $anioMinimo,
            'anioMaximo' => $anioMaximo,
        ];
    }

    /**
     * Calcula los conteos mensuales de préstamos y transferencias o el top 8 de funcionarios.
     *
     * @return array{filas: list<array{tipo?: string, label: string, total: int}>, colores: list<string>, totalMes: int}
     */
    private function calcularEstadisticas(string $inicioMes, string $finMes, string $vista): array
    {
        $baseQuery = movimientos::query()
            ->whereNull('movimientos.anulado_at')
            ->whereIn('movimientos.tipo_movimiento', [movimientos::PRESTAMO, movimientos::TRANSFERENCIA])
            ->whereBetween('movimientos.fecha_movimiento', [$inicioMes.' 00:00:00', $finMes.' 23:59:59']);

        if ($vista === 'usuarios') {
            $filas = (clone $baseQuery)
                ->join('funcionarios', 'funcionarios.id', '=', 'movimientos.funcionario_id')
                ->selectRaw('funcionarios.nombre as label, COUNT(movimientos.id) as total')
                ->groupBy('movimientos.funcionario_id', 'funcionarios.nombre')
                ->orderByDesc('total')
                ->orderBy('funcionarios.nombre')
                ->limit(8)
                ->get()
                ->map(fn ($fila): array => [
                    'label' => (string) $fila->label,
                    'total' => (int) $fila->total,
                ])
                ->all();

            $colores = [];
            foreach ($filas as $indice => $_) {
                $colores[] = self::PALETA_USUARIOS[$indice % count(self::PALETA_USUARIOS)];
            }

            return [
                'filas' => $filas,
                'colores' => $colores,
                'totalMes' => (clone $baseQuery)->count(),
            ];
        }

        $conteos = (clone $baseQuery)
            ->selectRaw('tipo_movimiento, COUNT(*) as total')
            ->groupBy('tipo_movimiento')
            ->pluck('total', 'tipo_movimiento');

        $filas = array_map(fn (string $tipo): array => [
            'tipo' => $tipo,
            'label' => movimientos::ETIQUETAS[$tipo],
            'total' => (int) ($conteos[$tipo] ?? 0),
        ], [movimientos::PRESTAMO, movimientos::TRANSFERENCIA]);

        usort($filas, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $colores = array_map(
            static fn (array $fila): string => self::COLORES_TIPO[$fila['tipo']] ?? '#60a5fa',
            $filas
        );

        return [
            'filas' => $filas,
            'colores' => $colores,
            'totalMes' => array_sum(array_column($filas, 'total')),
        ];
    }

    /**
     * Cuenta los préstamos del mes según tengan unidades pendientes o estén cerrados.
     *
     * @return array{pendientes: int, cerrados: int}
     */
    private function contarPrestamosPorEstado(string $inicioMes, string $finMes): array
    {
        $resueltoSql = '(SELECT COALESCE(SUM(h.cantidad), 0) FROM movimientos h WHERE h.movimiento_origen_id = movimientos.id AND h.anulado_at IS NULL)';

        $resumen = movimientos::query()
            ->whereNull('anulado_at')
            ->where('tipo_movimiento', movimientos::PRESTAMO)
            ->whereBetween('fecha_movimiento', [$inicioMes.' 00:00:00', $finMes.' 23:59:59'])
            ->selectRaw("SUM(CASE WHEN cantidad > {$resueltoSql} THEN 1 ELSE 0 END) as pendientes")
            ->selectRaw("SUM(CASE WHEN cantidad <= {$resueltoSql} THEN 1 ELSE 0 END) as cerrados")
            ->first();

        return [
            'pendientes' => (int) ($resumen->pendientes ?? 0),
            'cerrados' => (int) ($resumen->cerrados ?? 0),
        ];
    }
}
