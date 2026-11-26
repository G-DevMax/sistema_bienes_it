<?php

namespace App\Http\Controllers\Tickets;

use App\Enums\TipoProblema;
use App\Http\Controllers\Controller;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\detalles_soluciones;
use App\Models\Informes\informes;
use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use App\Services\LoggerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function dashboard(Request $request): View
    {
        $datosMes = $this->resolverMes($request);
        $inicioMes = $datosMes['inicioMes'];
        $finMes = $datosMes['finMes'];

        /**
         * Obtiene el número de tickets por estado.
         * - Pendientes: tickets sin asignar y sin cerrar.
         * - Asignados: tickets asignados y sin cerrar.
         * - Cerrados: tickets cerrados, ya sea que se hayan cerrado en el mes o en otro mes.
         * Se unifica todo en una sola consulta para mayor eficiencia.
         */
        $conteos = Ticket::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->where(function ($q) use ($inicioMes, $finMes): void {
                $q->where(function ($sub) use ($inicioMes, $finMes): void {
                    $sub->whereIn('estado', [Ticket::ESTADO_PENDIENTE, Ticket::ESTADO_ASIGNADO])
                        ->whereBetween('fecha_creacion', [$inicioMes, $finMes]);
                })->orWhere(function ($sub) use ($inicioMes, $finMes): void {
                    $sub->where('estado', Ticket::ESTADO_CERRADO)
                        ->where(function ($fecha) use ($inicioMes, $finMes): void {
                            $fecha->where(function ($conCierre) use ($inicioMes, $finMes): void {
                                $conCierre->whereNotNull('fecha_cierre')
                                    ->whereBetween('fecha_cierre', [$inicioMes, $finMes]);
                            })->orWhere(function ($sinCierre) use ($inicioMes, $finMes): void {
                                $sinCierre->whereNull('fecha_cierre')
                                    ->whereBetween('fecha_creacion', [$inicioMes, $finMes]);
                            });
                        });
                });
            })
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $pendientes = (int) ($conteos[Ticket::ESTADO_PENDIENTE] ?? 0);
        $asignados = (int) ($conteos[Ticket::ESTADO_ASIGNADO] ?? 0);
        $cerrados = (int) ($conteos[Ticket::ESTADO_CERRADO] ?? 0);

        $ticketsRecientes = Ticket::with('funcionario')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $ultimoPendienteId = (int) (Ticket::where('estado', Ticket::ESTADO_PENDIENTE)->max('id') ?? 0);

        return view('Tickets.dashboard', [
            'pendientes' => $pendientes,
            'asignados' => $asignados,
            'cerrados' => $cerrados,
            'ticketsRecientes' => $ticketsRecientes,
            'ultimoPendienteId' => $ultimoPendienteId,
            'mes' => $datosMes['mes'],
            'nombreMes' => $datosMes['nombreMes'],
            'nombreMesSimple' => $datosMes['nombreMesSimple'],
            'anio' => $datosMes['anio'],
            'anioMinimo' => $datosMes['anioMinimo'],
            'anioMaximo' => $datosMes['anioMaximo'],
        ]);
    }

    public function estadisticas(Request $request): View
    {
        $datosMes = $this->resolverMes($request);
        $vista = $request->query('vista') === 'usuarios' ? 'usuarios' : 'problemas';
        $estadisticas = $this->calcularEstadisticas($datosMes['inicioMes'], $datosMes['finMes'], $vista);

        return view('Tickets.estadisticas', [
            'vista' => $vista,
            'filasTabla' => $estadisticas['filas'],
            'colores' => $estadisticas['colores'],
            'totalMes' => $estadisticas['totalMes'],
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
        $vista = $request->query('vista') === 'usuarios' ? 'usuarios' : 'problemas';
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

    public function estadoPendientes(Request $request): JsonResponse
    {
        $mes = Ticket::mesValidoEnRango($request->query('mes'));
        $queryPendientes = Ticket::where('estado', Ticket::ESTADO_PENDIENTE);

        if ($mes !== null) {
            [$inicioMes, $finMes] = $this->rangoMes($mes);
            $queryPendientes->whereBetween('fecha_creacion', [$inicioMes, $finMes]);
        }

        $pendientes = $queryPendientes->count();
        $ultimo = Ticket::with('funcionario')
            ->where('estado', Ticket::ESTADO_PENDIENTE)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'pendientes' => $pendientes,
            'ultimo_id' => (int) ($ultimo?->id ?? 0),
            'ultimo_ticket' => $ultimo ? [
                'id' => $ultimo->id,
                'numero_ticket' => $ultimo->numero_ticket,
                'funcionario' => $ultimo->funcionario->nombre ?? 'Funcionario',
                'tipo_problema' => $ultimo->tipo_problema,
                'descripcion_problema' => $ultimo->descripcion_problema,
            ] : null,
        ]);
    }

    public function index(Request $request): View
    {
        $busqueda = is_string($request->search) ? ltrim(trim($request->search), '#') : $request->search;

        $query = Ticket::with(['funcionario', 'tecnicoAsignado'])
            ->buscadorUniversal($busqueda)
            ->orderByDesc('fecha_creacion')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        if ($estado = $request->query('estado')) {
            if (in_array($estado, Ticket::ESTADOS, true)) {
                $query->where('estado', $estado);
            }
        }

        $tickets = $query->paginate(10)->withQueryString();
        $tiposProblema = TipoProblema::values();

        return view('Tickets.index', compact('tickets', 'tiposProblema'));
    }

    public function create(): View
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $tiposProblema = TipoProblema::values();
        $detallesProblemas = detalles_problemas::orderBy('detalle')->get(['id', 'tipo_problema', 'detalle']);

        return view('Tickets.create', compact('funcionarios', 'tiposProblema', 'detallesProblemas'));
    }

    public function createManual(): View
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $tiposProblema = TipoProblema::values();
        $detallesProblemas = detalles_problemas::orderBy('detalle')->get(['id', 'tipo_problema', 'detalle']);

        return view('Tickets.create_manual', compact('funcionarios', 'tiposProblema', 'detallesProblemas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarTicket($request);

        try {
            $datos['imagen_problema'] = $this->guardarImagenProblema($request);
            $ticket = $this->crearTicket($datos);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear ticket', [
                'funcionario_id' => $request->input('funcionario_id'),
                'tipo_problema' => $request->input('tipo_problema'),
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.create')
                ->withInput()
                ->with('error', $this->mensajeError($e, 'No se pudo registrar el ticket. Intente nuevamente.'));
        }

        $request->session()->put('ultimo_ticket_creado_numero', $ticket->numero_ticket);

        return redirect()->route('tickets.visual');
    }

    public function visual(Request $request): View|RedirectResponse
    {
        $numeroTicket = $request->session()->get('ultimo_ticket_creado_numero');

        if (! is_string($numeroTicket) || $numeroTicket === '') {
            return redirect()->route('tickets.create');
        }

        return view('Tickets.visual_ticket', ['numeroTicket' => $numeroTicket]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $datos = $this->validarTicket($request);

        try {
            $datos['imagen_problema'] = $this->guardarImagenProblema($request);
            $ticket = $this->crearTicket($datos);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear ticket manual', [
                'funcionario_id' => $request->input('funcionario_id'),
                'tipo_problema' => $request->input('tipo_problema'),
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.create-manual')
                ->withInput()
                ->with('error', $this->mensajeError($e, 'No se pudo registrar el ticket. Intente nuevamente.'));
        }

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket #'.$ticket->numero_ticket.' creado exitosamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validarTicket(Request $request): array
    {
        $tipoProblema = $request->input('tipo_problema');
        $esReunion = $tipoProblema === Ticket::TIPO_ASISTENCIA_REUNION;
        $esConsulta = $tipoProblema === Ticket::TIPO_CONSULTA;
        $esInsumos = $tipoProblema === Ticket::TIPO_INSUMOS;

        // "Otro (especificar)" puede llegar como "" o "otro" desde el selector de problema común
        // Para Consulta se fuerza siempre el modo descripción directa ("otro")
        $detalleId = $request->input('detalle_id');
        if ($esReunion || $esConsulta || $detalleId === '' || $detalleId === 'otro') {
            $request->merge(['detalle_id' => null]);
            $detalleId = null;
        }

        return $request->validate([
            'funcionario_id' => ['required', Rule::exists('funcionarios', 'id')->where('estado_funcionario', 1)],
            'tipo_problema' => ['required', Rule::enum(TipoProblema::class)],
            'detalle_id' => [
                'nullable',
                Rule::exists('detalles_problemas', 'id')->where('tipo_problema', $tipoProblema),
            ],
            'descripcion_problema' => [
                Rule::requiredIf(! $esReunion && empty($detalleId)),
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($esReunion, $detalleId): void {
                    if ($esReunion || ! empty($detalleId)) {
                        return;
                    }
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('El título del problema no puede superar las 100 palabras.');
                    }
                },
            ],
            'cantidad_insumo' => [
                Rule::requiredIf($esInsumos),
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'fecha_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'date', 'after_or_equal:2026-01-01'],
            'horario_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'string', 'max:100'],
            'detalles_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'string', 'max:500'],
            'imagen_problema' => [
                'nullable',
                'file',
                'image',
                'mimetypes:image/webp,image/jpeg,image/png,image/gif',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $ruta = $value instanceof UploadedFile ? $value->getRealPath() : false;
                    $mime = $ruta !== false ? (new \finfo(FILEINFO_MIME_TYPE))->file($ruta) : false;
                    if (! in_array($mime, ['image/webp', 'image/jpeg', 'image/png', 'image/gif'], true)) {
                        $fail('El archivo adjunto debe ser una imagen real (WebP, JPEG, PNG o GIF).');
                    }
                },
            ],
        ], [
            'funcionario_id.required' => 'Debe seleccionar un funcionario.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido o está inactivo.',
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'detalle_id.exists' => 'El problema común seleccionado no corresponde al tipo de problema indicado.',
            'descripcion_problema.required' => 'Debe escribir un título breve para el problema.',
            'descripcion_problema.max' => 'El título del problema no puede superar los 255 caracteres.',
            'cantidad_insumo.required' => 'Debe especificar la cantidad solicitada.',
            'cantidad_insumo.integer' => 'La cantidad debe ser un número entero.',
            'cantidad_insumo.min' => 'La cantidad mínima permitida es 1.',
            'cantidad_insumo.max' => 'La cantidad máxima permitida es 12.',
            'fecha_reunion.required' => 'Debe indicar la fecha de la reunión.',
            'fecha_reunion.date' => 'La fecha de la reunión no es válida.',
            'fecha_reunion.after_or_equal' => 'La fecha de la reunión debe ser a partir del 01/01/2026.',
            'horario_reunion.required' => 'Debe indicar la hora de comienzo y finalización aproximada de la reunión.',
            'horario_reunion.max' => 'El horario de la reunión no puede superar los 100 caracteres.',
            'detalles_reunion.required' => 'Debe indicar dónde será la reunión y sus observaciones.',
            'detalles_reunion.max' => 'Los detalles de la reunión no pueden superar los 500 caracteres.',
            'imagen_problema.file' => 'El archivo adjunto no es válido.',
            'imagen_problema.image' => 'El archivo adjunto debe ser una imagen real (WebP, JPEG, PNG o GIF).',
            'imagen_problema.mimetypes' => 'El archivo adjunto debe ser una imagen real (WebP, JPEG, PNG o GIF).',
            'imagen_problema.max' => 'La imagen adjunta no puede superar los 10 MB.',
        ]);
    }

    /**
     * Verifica el MIME real del archivo adjunto y lo almacena en public/uploads/tickets/YYYY/MM/.
     */
    private function guardarImagenProblema(Request $request): ?string
    {
        if (! $request->hasFile('imagen_problema')) {
            return null;
        }

        $archivo = $request->file('imagen_problema');
        if ($archivo === null || ! $archivo->isValid()) {
            return null;
        }

        $rutaTemporal = $archivo->getRealPath();
        $mimeReal = $rutaTemporal !== false ? (new \finfo(FILEINFO_MIME_TYPE))->file($rutaTemporal) : false;

        if (! in_array($mimeReal, ['image/webp', 'image/jpeg', 'image/png', 'image/gif'], true)) {
            throw new \RuntimeException('El archivo adjunto no es una imagen válida.');
        }

        $subcarpeta = 'uploads/tickets/'.now()->format('Y/m');
        $directorio = public_path($subcarpeta);

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombreArchivo = uniqid().'.webp';
        if (is_uploaded_file($rutaTemporal)) {
            $archivo->move($directorio, $nombreArchivo);
        } else {
            copy($rutaTemporal, $directorio.DIRECTORY_SEPARATOR.$nombreArchivo);
        }

        return '/'.$subcarpeta.'/'.$nombreArchivo;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crearTicket(array $datos): Ticket
    {
        $esReunion = $datos['tipo_problema'] === Ticket::TIPO_ASISTENCIA_REUNION;
        $detalle = (! $esReunion && ! empty($datos['detalle_id']))
            ? detalles_problemas::find($datos['detalle_id'])
            : null;

        $descripcion = Ticket::formatearDescripcion(
            $datos['tipo_problema'],
            $detalle !== null ? $detalle->detalle : ($datos['descripcion_problema'] ?? null),
            $datos['fecha_reunion'] ?? null,
            $datos['horario_reunion'] ?? null,
            $datos['detalles_reunion'] ?? null,
            isset($datos['cantidad_insumo']) ? (int) $datos['cantidad_insumo'] : null
        );

        $ticket = DB::transaction(function () use ($datos, $detalle, $descripcion): Ticket {
            $ticket = Ticket::create([
                'funcionario_id' => $datos['funcionario_id'],
                'tipo_problema' => $datos['tipo_problema'],
                'detalles_id' => $detalle?->id,
                'descripcion_problema' => $descripcion,
                'imagen_problema' => $datos['imagen_problema'] ?? null,
                'estado' => Ticket::ESTADO_PENDIENTE,
                'fecha_creacion' => now()->toDateString(),
            ]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'funcionario_id' => $ticket->funcionario_id,
                'accion' => 'Ticket creado',
                'responsable_id' => Auth::id(),
                'fecha' => now()->toDateString(),
            ]);

            return $ticket;
        });

        LoggerService::info('Ticket creado', [
            'ticket_id' => $ticket->id,
            'numero_ticket' => $ticket->numero_ticket,
            'funcionario_id' => $ticket->funcionario_id,
            'tipo_problema' => $ticket->tipo_problema,
            'detalles_id' => $ticket->detalles_id,
            'user_id' => Auth::id(),
        ]);

        return $ticket;
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['funcionario.dependencia', 'tecnicoAsignado', 'detalle', 'solucion', 'informe', 'historial.responsable', 'historial.funcionario']);
        $tecnicos = User::where('estado_user', 1)
            ->whereIn('rol_id', [rol::ADMIN, rol::TECNICO_TERCERIZADO])
            ->orderBy('name')
            ->get();
        $solucionesComunes = detalles_soluciones::where('tipo_problema', $ticket->tipo_problema)
            ->orderBy('solucion')
            ->get();

        return view('Tickets.show', compact('ticket', 'tecnicos', 'solucionesComunes'));
    }

    public function asignar(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->estaCerrado()) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'No se puede asignar un ticket que ya se encuentra cerrado.');
        }

        $datos = $request->validate([
            'asignado_a' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('estado_user', 1)
                    ->whereIn('rol_id', [rol::ADMIN, rol::TECNICO_TERCERIZADO])),
            ],
        ], [
            'asignado_a.required' => 'Debe seleccionar un técnico para asignar el ticket.',
            'asignado_a.exists' => 'El técnico seleccionado no es válido o está inactivo.',
        ]);

        try {
            $tecnico = User::findOrFail($datos['asignado_a']);

            $errorEstado = DB::transaction(function () use ($ticket, $tecnico): ?string {
                $ticketBloqueado = Ticket::lockForUpdate()->findOrFail($ticket->id);

                if ($ticketBloqueado->estaCerrado()) {
                    return 'No se puede asignar un ticket que ya se encuentra cerrado.';
                }

                $ticketBloqueado->update([
                    'asignado_a' => $tecnico->id,
                    'estado' => Ticket::ESTADO_ASIGNADO,
                ]);

                TicketHistory::create([
                    'ticket_id' => $ticketBloqueado->id,
                    'funcionario_id' => $ticketBloqueado->funcionario_id,
                    'accion' => 'Ticket asignado a '.$tecnico->name,
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);

                return null;
            });

            if ($errorEstado !== null) {
                return redirect()->route('tickets.show', $ticket)
                    ->with('error', $errorEstado);
            }

            LoggerService::info('Ticket asignado', [
                'ticket_id' => $ticket->id,
                'numero_ticket' => $ticket->numero_ticket,
                'asignado_a' => $tecnico->id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al asignar ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.show', $ticket)
                ->with('error', $this->mensajeError($e, 'No se pudo asignar el ticket.'));
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket asignado correctamente.');
    }

    public function cerrar(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->estaCerrado()) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'El ticket ya se encuentra cerrado.');
        }

        if ($ticket->estado === Ticket::ESTADO_PENDIENTE) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Debe asignar un técnico antes de cerrar el ticket.');
        }

        // "Otro (especificar)" puede llegar como "" o "otro" desde el selector
        $solucionId = $request->input('solucion_id');
        if ($solucionId === '' || $solucionId === 'otro') {
            $request->merge(['solucion_id' => null]);
            $solucionId = null;
        }

        $datos = $request->validate([
            'solucion_id' => [
                'nullable',
                Rule::exists('detalles_soluciones', 'id')->where('tipo_problema', $ticket->tipo_problema),
            ],
            'observacion' => [Rule::requiredIf(empty($solucionId)), 'nullable', 'string', 'max:2000'],
        ], [
            'solucion_id.exists' => 'La solución común seleccionada no corresponde al tipo de problema del ticket.',
            'observacion.required' => 'Debe especificar la solución en observaciones al seleccionar "Otro (especificar)".',
            'observacion.max' => 'La observación no puede superar los 2000 caracteres.',
        ]);

        $observacion = isset($datos['observacion']) && trim($datos['observacion']) !== ''
            ? trim($datos['observacion'])
            : null;
        $solucionSeleccionadaId = $datos['solucion_id'] ?? null;
        $informe = null;

        try {
            $errorEstado = DB::transaction(function () use ($ticket, $solucionSeleccionadaId, $observacion, &$informe): ?string {
                $ticketBloqueado = Ticket::lockForUpdate()->findOrFail($ticket->id);

                if ($ticketBloqueado->estaCerrado()) {
                    return 'El ticket ya se encuentra cerrado.';
                }

                if ($ticketBloqueado->estado === Ticket::ESTADO_PENDIENTE) {
                    return 'Debe asignar un técnico antes de cerrar el ticket.';
                }

                $solucion = $solucionSeleccionadaId !== null
                    ? detalles_soluciones::find($solucionSeleccionadaId)
                    : null;

                $ticketBloqueado->update([
                    'estado' => Ticket::ESTADO_CERRADO,
                    'fecha_cierre' => now()->toDateString(),
                    'solucion_id' => $solucion?->id,
                    'observacion' => $observacion,
                ]);

                $accion = $solucion !== null
                    ? 'Ticket cerrado ('.$solucion->solucion.')'.($observacion !== null ? ': '.$observacion : '')
                    : 'Ticket cerrado: '.$observacion;

                TicketHistory::create([
                    'ticket_id' => $ticketBloqueado->id,
                    'funcionario_id' => $ticketBloqueado->funcionario_id,
                    'accion' => Str::limit($accion, 250),
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);

                $informe = informes::create($ticketBloqueado->datosParaInforme($solucion?->id, $observacion, Auth::id()));

                return null;
            });

            if ($errorEstado !== null) {
                return redirect()->route('tickets.show', $ticket)
                    ->with('error', $errorEstado);
            }

            LoggerService::info('Ticket cerrado', [
                'ticket_id' => $ticket->id,
                'numero_ticket' => $ticket->numero_ticket,
                'solucion_id' => $solucionSeleccionadaId,
                'informe_id' => $informe?->id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al cerrar ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.show', $ticket)
                ->with('error', $this->mensajeError($e, 'No se pudo cerrar el ticket.'));
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket cerrado exitosamente y registrado en Informes (#'.$informe->id.').');
    }

    /**
     * Resuelve el mes seleccionado y sus límites en formato Y-m-d junto a etiquetas para la vista.
     *
     * @return array{mes: string, inicioMes: string, finMes: string, nombreMes: string, nombreMesSimple: string, anio: string, anioMinimo: int, anioMaximo: int}
     */
    private function resolverMes(Request $request): array
    {
        $rangoAnios = Ticket::rangoAniosDashboard();
        $anioMinimo = $rangoAnios['min'];
        $anioMaximo = $rangoAnios['max'];

        $mes = Ticket::mesValidoEnRango($request->query('mes'), $anioMinimo, max($anioMaximo, now()->addDay()->year))
            ?? sprintf('%04d-%02d', max(now()->year, $anioMinimo), now()->month);

        [$year, $month] = explode('-', $mes);
        $anioMaximo = max($anioMaximo, (int) $year);
        [$inicioMes, $finMes] = $this->rangoMes($mes);

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
     * Calcula los conteos mensuales ordenados de mayor a menor para tipos de problema o top 8 usuarios.
     *
     * @return array{filas: list<array{label: string, total: int}>, colores: list<string>, totalMes: int}
     */
    private function calcularEstadisticas(string $inicioMes, string $finMes, string $vista): array
    {
        if ($vista === 'usuarios') {
            $filas = Ticket::query()
                ->join('funcionarios', 'funcionarios.id', '=', 'tickets.funcionario_id')
                ->selectRaw('funcionarios.nombre as label, COUNT(tickets.id) as total')
                ->whereBetween('tickets.fecha_creacion', [$inicioMes, $finMes])
                ->groupBy('tickets.funcionario_id', 'funcionarios.nombre')
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
                'colores' => array_fill(0, count($filas), '#1CD6CE'),
                'totalMes' => Ticket::whereBetween('fecha_creacion', [$inicioMes, $finMes])->count(),
            ];
        }

        $conteos = Ticket::query()
            ->selectRaw('tipo_problema, COUNT(*) as total')
            ->whereBetween('fecha_creacion', [$inicioMes, $finMes])
            ->groupBy('tipo_problema')
            ->pluck('total', 'tipo_problema');

        $filas = array_map(fn (string $tipo): array => [
            'label' => $tipo,
            'total' => (int) ($conteos[$tipo] ?? 0),
        ], TipoProblema::values());

        usort($filas, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return [
            'filas' => $filas,
            'colores' => array_map(static fn (array $fila): string => Ticket::colorTipo($fila['label']), $filas),
            'totalMes' => array_sum(array_column($filas, 'total')),
        ];
    }

    /**
     * Devuelve [inicioMes, finMes] en formato Y-m-d para filtrar rangos sin funciones SQL sobre columnas.
     *
     * @return array{0: string, 1: string}
     */
    private function rangoMes(string $mes): array
    {
        [$year, $month] = explode('-', $mes);
        $inicio = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();

        return [$inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString()];
    }
}
