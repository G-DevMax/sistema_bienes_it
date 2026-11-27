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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $datosMes = $this->resolverMesMovimientos($request);
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
        $datosMes = $this->resolverMesMovimientos($request);
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

        if ($request->boolean('pendientes')) {
            $query->prestamosPendientes();
        }

        $movimientos = $query->paginate(15)->withQueryString();
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

    public function store(Request $request): RedirectResponse
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

        return $this->ejecutarAccion(function () use ($datos, $tipo): RedirectResponse {
            $loteId = Str::uuid()->toString();
            $primerMovimientoId = DB::transaction(function () use ($datos, $tipo, $loteId): ?int {
                $primerMovimientoId = null;

                foreach ($datos['item_id'] as $index => $itemId) {
                    $datosIndividuales = array_merge($datos, [
                        'item_id' => $itemId,
                        'cantidad' => $datos['cantidad'][$index],
                    ]);

                    $mov = $tipo === movimientos::BAJA
                        ? $this->movimientoService->registrarBaja($datosIndividuales, Auth::id())
                        : $this->movimientoService->registrarEntrega($datosIndividuales, Auth::id());

                    $mov->update(['lote_id' => $loteId]);
                    $primerMovimientoId ??= $mov->id;

                    LoggerService::info('Movimiento registrado', [
                        'id' => $mov->id,
                        'lote_id' => $loteId,
                        'tipo' => $mov->tipo_movimiento,
                        'item' => $mov->item_id,
                        'funcionario' => $mov->funcionario_id,
                        'cantidad' => $mov->cantidad,
                        'fecha' => $mov->fecha_movimiento,
                    ]);
                }

                return $primerMovimientoId;
            });

            return redirect()->route('movimientos.show', $primerMovimientoId)
                ->with('success', 'Movimiento(s) registrado(s) correctamente.');
        }, 'movimientos.index', 'Movimiento(s) registrado(s) correctamente.', 'No se pudo registrar el movimiento.', 'Error al registrar el movimiento', [
            'tipo' => $request->tipo_movimiento,
        ]);
    }

    public function show(movimientos $movimiento)
    {
        $movimiento->load([
            'item.estado',
            'funcionario',
            'usuario',
            'anuladoPor',
            'origen.funcionario',
            'hijos' => fn ($q) => $q->with(['usuario', 'anuladoPor'])->orderBy('fecha_movimiento'),
        ]);

        return view('Bienes.movimientos.show', compact('movimiento'));
    }

    public function exportarPlanilla(movimientos $movimiento, PlanillaMovimientoService $planilla): RedirectResponse|StreamedResponse
    {
        if ($movimiento->estaAnulado()) {
            return redirect()->route('movimientos.show', $movimiento)->with('error', 'No se puede imprimir la planilla de un movimiento anulado.');
        }

        $movimientos = $movimiento->lote_id
            ? movimientos::where('lote_id', $movimiento->lote_id)->orderBy('id')->get()
            : collect([$movimiento]);

        return $this->descargarXlsx($planilla->generar($movimientos), $planilla->nombreArchivo($movimientos));
    }

    public function devolver(Request $request, string $id): RedirectResponse
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

        return $this->ejecutarAccion(function () use ($id, $datos): void {
            $this->movimientoService->registrarDevolucion((int) $id, $datos, Auth::id());

            LoggerService::info('Devolución registrada', [
                'id' => $id,
                'cantidad' => $datos['cantidad'],
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        }, ['movimientos.show', $id], 'Devolución registrada; el stock fue actualizado.', 'No se pudo registrar la devolución.', 'Error al registrar la devolución', [
            'id' => $id,
            'cantidad' => $request->cantidad,
            'fecha_movimiento' => $request->fecha_movimiento,
            'observaciones' => $request->observaciones,
        ]);
    }

    public function bajaEntrega(Request $request, string $id): RedirectResponse
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

        return $this->ejecutarAccion(function () use ($id, $datos): void {
            $this->movimientoService->registrarBajaDesdeEntrega((int) $id, $datos, Auth::id());

            LoggerService::info('Baja desde entrega registrada', [
                'id' => $id,
                'cantidad' => $datos['cantidad'],
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'motivo' => $datos['motivo'],
                'observaciones' => $datos['observaciones'],
            ]);
        }, ['movimientos.show', $id], 'Baja registrada con nota de responsabilidad.', 'No se pudo registrar la baja desde entrega.', 'Error al registrar la baja desde entrega', [
            'id' => $id,
            'cantidad' => $request->cantidad,
            'fecha_movimiento' => $request->fecha_movimiento,
            'motivo' => $request->motivo,
            'observaciones' => $request->observaciones,
        ]);
    }

    public function anular(Request $request, string $id): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_anulacion' => ['required', 'string', 'max:255'],
        ], [
            'motivo_anulacion.required' => 'Indique el motivo de la anulación.',
            'motivo_anulacion.max' => 'El motivo de anulación no puede superar los 255 caracteres.',
        ]);

        return $this->ejecutarAccion(function () use ($id, $datos): void {
            $this->movimientoService->anular((int) $id, $datos['motivo_anulacion'], Auth::id());

            LoggerService::info('Movimiento anulado', [
                'id' => $id,
                'motivo_anulacion' => $datos['motivo_anulacion'],
            ]);
        }, ['movimientos.show', $id], 'Movimiento anulado y stock revertido.', 'No se pudo anular el movimiento.', 'Error al anular el movimiento', [
            'id' => $id,
            'motivo_anulacion' => $request->motivo_anulacion,
        ]);
    }

    public function generarInforme(Request $request, InformeMovimientoService $informe): StreamedResponse
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

        return $this->descargarXlsx(
            $informe->generar($movimientos),
            $informe->nombreArchivo($datos['tipo_movimiento'], $datos['fecha_desde'], $datos['fecha_hasta'])
        );
    }

    private function descargarXlsx(Spreadsheet $spreadsheet, string $nombreArchivo): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{mes: string, inicioMes: string, finMes: string, nombreMes: string, nombreMesSimple: string, anio: string, anioMinimo: int, anioMaximo: int}
     */
    private function resolverMesMovimientos(Request $request): array
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

        return $this->resolverMes($request, $anioMinimo, $anioMaximo);
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

            return [
                'filas' => $filas,
                'colores' => array_slice(self::PALETA_USUARIOS, 0, count($filas)),
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
        $resueltoSql = movimientos::SQL_HIJOS_ACTIVOS;

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
