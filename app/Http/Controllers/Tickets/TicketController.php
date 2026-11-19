<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function dashboard(Request $request): View
    {
        $rangoAnios = Ticket::rangoAniosDashboard();
        $anioMinimo = $rangoAnios['min'];
        $anioMaximo = $rangoAnios['max'];

        $mes = Ticket::mesValidoEnRango($request->query('mes'), $anioMinimo, max($anioMaximo, now()->addDay()->year))
            ?? sprintf('%04d-%02d', max(now()->year, $anioMinimo), now()->month);

        [$year, $month] = explode('-', $mes);
        $anioMaximo = max($anioMaximo, (int) $year);

        $pendientes = Ticket::where('estado', Ticket::ESTADO_PENDIENTE)
            ->whereYear('fecha_creacion', $year)
            ->whereMonth('fecha_creacion', $month)
            ->count();

        $asignados = Ticket::where('estado', Ticket::ESTADO_ASIGNADO)
            ->whereYear('fecha_creacion', $year)
            ->whereMonth('fecha_creacion', $month)
            ->count();

        $cerrados = Ticket::where('estado', Ticket::ESTADO_CERRADO)
            ->where(function ($q) use ($year, $month): void {
                $q->where(function ($sub) use ($year, $month): void {
                    $sub->whereNotNull('fecha_cierre')
                        ->whereYear('fecha_cierre', $year)
                        ->whereMonth('fecha_cierre', $month);
                })->orWhere(function ($sub) use ($year, $month): void {
                    $sub->whereNull('fecha_cierre')
                        ->whereYear('fecha_creacion', $year)
                        ->whereMonth('fecha_creacion', $month);
                });
            })
            ->count();

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
        $nombreMes = $nombreMesSimple.' '.$year;
        $anio = $year;

        $ticketsRecientes = Ticket::with('funcionario')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $ultimoPendienteId = (int) (Ticket::where('estado', Ticket::ESTADO_PENDIENTE)->max('id') ?? 0);

        return view('Tickets.dashboard', compact(
            'pendientes',
            'asignados',
            'cerrados',
            'ticketsRecientes',
            'ultimoPendienteId',
            'mes',
            'nombreMes',
            'nombreMesSimple',
            'anio',
            'anioMinimo',
            'anioMaximo'
        ));
    }

    public function estadoPendientes(Request $request): JsonResponse
    {
        $mes = Ticket::mesValidoEnRango($request->query('mes'));
        $queryPendientes = Ticket::where('estado', Ticket::ESTADO_PENDIENTE);

        if ($mes !== null) {
            [$year, $month] = explode('-', $mes);
            $queryPendientes->whereYear('fecha_creacion', $year)->whereMonth('fecha_creacion', $month);
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
                'funcionario' => $ultimo->funcionario->nombre ?? 'Funcionario',
                'tipo_problema' => $ultimo->tipo_problema,
                'descripcion_problema' => $ultimo->descripcion_problema,
            ] : null,
        ]);
    }

    public function index(Request $request): View
    {
        $query = Ticket::with(['funcionario', 'tecnicoAsignado'])
            ->buscadorUniversal($request->search)
            ->orderByDesc('fecha_creacion')
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo_problema', $tipo);
        }

        $tickets = $query->paginate(10)->withQueryString();
        $tiposProblema = Ticket::TIPOS_PROBLEMA;

        return view('Tickets.index', compact('tickets', 'tiposProblema'));
    }

    public function create(): View
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $tiposProblema = Ticket::TIPOS_PROBLEMA;

        return view('Tickets.create', compact('funcionarios', 'tiposProblema'));
    }

    public function createManual(): View
    {
        $funcionarios = funcionarios::where('estado_funcionario', 1)->orderBy('nombre')->get();
        $tiposProblema = Ticket::TIPOS_PROBLEMA;

        return view('Tickets.create_manual', compact('funcionarios', 'tiposProblema'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarTicket($request);

        try {
            $ticket = $this->crearTicket($datos);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear ticket', [
                'funcionario_id' => $request->input('funcionario_id'),
                'tipo_problema' => $request->input('tipo_problema'),
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.create')
                ->withInput()
                ->with('error', 'No se pudo registrar el ticket. Intente nuevamente.');
        }

        return redirect()->route('tickets.create')
            ->with('success', 'Tu ticket #'.$ticket->id.' fue enviado correctamente. En breve un técnico le asistirá con su problema.');
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $datos = $this->validarTicket($request);

        try {
            $ticket = $this->crearTicket($datos);
        } catch (\Exception $e) {
            LoggerService::error('Error al crear ticket manual', [
                'funcionario_id' => $request->input('funcionario_id'),
                'tipo_problema' => $request->input('tipo_problema'),
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.create-manual')
                ->withInput()
                ->with('error', 'No se pudo registrar el ticket. Intente nuevamente.');
        }

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket #'.$ticket->id.' creado exitosamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validarTicket(Request $request): array
    {
        $esReunion = $request->input('tipo_problema') === Ticket::TIPO_ASISTENCIA_REUNION;

        return $request->validate([
            'funcionario_id' => ['required', Rule::exists('funcionarios', 'id')->where('estado_funcionario', 1)],
            'tipo_problema' => ['required', Rule::in(Ticket::TIPOS_PROBLEMA)],
            'descripcion_problema' => [
                Rule::requiredIf(! $esReunion),
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($esReunion): void {
                    if ($esReunion) {
                        return;
                    }
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('El título del problema no puede superar las 100 palabras.');
                    }
                },
            ],
            'fecha_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'date', 'after_or_equal:2026-01-01'],
            'horario_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'string', 'max:100'],
            'detalles_reunion' => [Rule::requiredIf($esReunion), 'nullable', 'string', 'max:500'],
        ], [
            'funcionario_id.required' => 'Debe seleccionar un funcionario.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido o está inactivo.',
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'descripcion_problema.required' => 'Debe escribir un título breve para el problema.',
            'descripcion_problema.max' => 'El título del problema no puede superar los 255 caracteres.',
            'fecha_reunion.required' => 'Debe indicar la fecha de la reunión.',
            'fecha_reunion.date' => 'La fecha de la reunión no es válida.',
            'fecha_reunion.after_or_equal' => 'La fecha de la reunión debe ser a partir del 01/01/2026.',
            'horario_reunion.required' => 'Debe indicar la hora de comienzo y finalización aproximada de la reunión.',
            'horario_reunion.max' => 'El horario de la reunión no puede superar los 100 caracteres.',
            'detalles_reunion.required' => 'Debe indicar dónde será la reunión y sus observaciones.',
            'detalles_reunion.max' => 'Los detalles de la reunión no pueden superar los 500 caracteres.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crearTicket(array $datos): Ticket
    {
        $descripcion = Ticket::formatearDescripcion(
            $datos['tipo_problema'],
            $datos['descripcion_problema'] ?? null,
            $datos['fecha_reunion'] ?? null,
            $datos['horario_reunion'] ?? null,
            $datos['detalles_reunion'] ?? null
        );

        $ticket = DB::transaction(function () use ($datos, $descripcion): Ticket {
            $ticket = Ticket::create([
                'funcionario_id' => $datos['funcionario_id'],
                'tipo_problema' => $datos['tipo_problema'],
                'descripcion_problema' => $descripcion,
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
            'funcionario_id' => $ticket->funcionario_id,
            'tipo_problema' => $ticket->tipo_problema,
            'user_id' => Auth::id(),
        ]);

        return $ticket;
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['funcionario.dependencia', 'tecnicoAsignado', 'detalle', 'historial.responsable', 'historial.funcionario']);
        $tecnicos = User::where('estado_user', 1)
            ->whereIn('rol_id', [rol::ADMIN, rol::TECNICO_TERCERIZADO])
            ->orderBy('name')
            ->get();
        $detallesComunes = detalles_problemas::where('tipo_problema', $ticket->tipo_problema)
            ->orderBy('detalle')
            ->get();

        return view('Tickets.show', compact('ticket', 'tecnicos', 'detallesComunes'));
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
                'asignado_a' => $tecnico->id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al asignar ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Error al asignar el ticket: '.$e->getMessage());
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
        $detalleId = $request->input('detalle_id');
        if ($detalleId === '' || $detalleId === 'otro') {
            $request->merge(['detalle_id' => null]);
            $detalleId = null;
        }

        $datos = $request->validate([
            'detalle_id' => [
                'nullable',
                Rule::exists('detalles_problemas', 'id')->where('tipo_problema', $ticket->tipo_problema),
            ],
            'observacion' => [Rule::requiredIf(empty($detalleId)), 'nullable', 'string', 'max:2000'],
        ], [
            'detalle_id.exists' => 'El problema común seleccionado no corresponde al tipo de problema del ticket.',
            'observacion.required' => 'Debe especificar el problema y la solución en observaciones al seleccionar "Otro (especificar)".',
            'observacion.max' => 'La observación no puede superar los 2000 caracteres.',
        ]);

        $observacion = isset($datos['observacion']) && trim($datos['observacion']) !== ''
            ? trim($datos['observacion'])
            : null;
        $detalleSeleccionadoId = $datos['detalle_id'] ?? null;
        $informe = null;

        try {
            $errorEstado = DB::transaction(function () use ($ticket, $detalleSeleccionadoId, $observacion, &$informe): ?string {
                $ticketBloqueado = Ticket::lockForUpdate()->findOrFail($ticket->id);

                if ($ticketBloqueado->estaCerrado()) {
                    return 'El ticket ya se encuentra cerrado.';
                }

                if ($ticketBloqueado->estado === Ticket::ESTADO_PENDIENTE) {
                    return 'Debe asignar un técnico antes de cerrar el ticket.';
                }

                $detalle = $detalleSeleccionadoId !== null
                    ? detalles_problemas::find($detalleSeleccionadoId)
                    : null;

                $ticketBloqueado->update([
                    'estado' => Ticket::ESTADO_CERRADO,
                    'fecha_cierre' => now()->toDateString(),
                    'detalle_id' => $detalle?->id,
                    'observacion' => $observacion,
                ]);

                $accion = $detalle !== null
                    ? 'Ticket cerrado ('.$detalle->detalle.')'.($observacion !== null ? ': '.$observacion : '')
                    : 'Ticket cerrado: '.$observacion;

                TicketHistory::create([
                    'ticket_id' => $ticketBloqueado->id,
                    'funcionario_id' => $ticketBloqueado->funcionario_id,
                    'accion' => Str::limit($accion, 250),
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);

                $informe = informes::create($ticketBloqueado->datosParaInforme($detalle?->id, $observacion));

                return null;
            });

            if ($errorEstado !== null) {
                return redirect()->route('tickets.show', $ticket)
                    ->with('error', $errorEstado);
            }

            LoggerService::info('Ticket cerrado', [
                'ticket_id' => $ticket->id,
                'detalle_id' => $detalleSeleccionadoId,
                'informe_id' => $informe?->id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al cerrar ticket', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
            ], $e);

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Error al cerrar el ticket: '.$e->getMessage());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket cerrado exitosamente y registrado en Informes (#'.$informe->id.').');
    }
}
