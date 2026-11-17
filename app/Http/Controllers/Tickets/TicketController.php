<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use App\Services\LoggerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function dashboard(): View
    {
        $conteos = Ticket::query()
            ->selectRaw('estado, COUNT(*) as total')
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

        return view('Tickets.dashboard', compact('pendientes', 'asignados', 'cerrados', 'ticketsRecientes'));
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

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'funcionario_id' => ['required', Rule::exists('funcionarios', 'id')->where('estado_funcionario', 1)],
            'tipo_problema' => ['required', Rule::in(Ticket::TIPOS_PROBLEMA)],
            'descripcion_problema' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
                    if (count($palabras) > 100) {
                        $fail('El título del problema no puede superar las 100 palabras.');
                    }
                },
            ],
        ], [
            'funcionario_id.required' => 'Debe seleccionar su nombre de funcionario.',
            'funcionario_id.exists' => 'El funcionario seleccionado no es válido o está inactivo.',
            'tipo_problema.required' => 'Debe seleccionar el tipo de problema.',
            'tipo_problema.in' => 'El tipo de problema seleccionado no es válido.',
            'descripcion_problema.required' => 'Debe escribir un título breve para su problema.',
            'descripcion_problema.max' => 'El título del problema no puede superar los 255 caracteres.',
        ]);

        try {
            $ticket = DB::transaction(function () use ($datos): Ticket {
                $ticket = Ticket::create([
                    'funcionario_id' => $datos['funcionario_id'],
                    'tipo_problema' => $datos['tipo_problema'],
                    'descripcion_problema' => trim($datos['descripcion_problema']),
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

    public function show(Ticket $ticket): View
    {
        $ticket->load(['funcionario.dependencia', 'tecnicoAsignado', 'detalle', 'historial.responsable', 'historial.funcionario']);
        $tecnicos = User::where('estado_user', 1)->orderBy('name')->get();
        $detallesComunes = detalles_problemas::where('tipo_problema', Ticket::tipoParaDetalle($ticket->tipo_problema))
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
            'asignado_a' => ['required', Rule::exists('users', 'id')->where('estado_user', 1)],
        ], [
            'asignado_a.required' => 'Debe seleccionar un técnico para asignar el ticket.',
            'asignado_a.exists' => 'El técnico seleccionado no es válido o está inactivo.',
        ]);

        try {
            $tecnico = User::findOrFail($datos['asignado_a']);

            DB::transaction(function () use ($ticket, $tecnico): void {
                $ticket->update([
                    'asignado_a' => $tecnico->id,
                    'estado' => Ticket::ESTADO_ASIGNADO,
                ]);

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'funcionario_id' => $ticket->funcionario_id,
                    'accion' => 'Ticket asignado a '.$tecnico->name,
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);
            });

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
                Rule::exists('detalles_problemas', 'id')->where('tipo_problema', Ticket::tipoParaDetalle($ticket->tipo_problema)),
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

        try {
            DB::transaction(function () use ($ticket, $detalleSeleccionadoId, $observacion): void {
                $detalle = $detalleSeleccionadoId !== null
                    ? detalles_problemas::find($detalleSeleccionadoId)
                    : null;

                $ticket->update([
                    'estado' => Ticket::ESTADO_CERRADO,
                    'fecha_cierre' => now()->toDateString(),
                    'detalle_id' => $detalle?->id,
                    'observacion' => $observacion,
                ]);

                $accion = $detalle !== null
                    ? 'Ticket cerrado ('.$detalle->detalle.')'.($observacion !== null ? ': '.$observacion : '')
                    : 'Ticket cerrado: '.$observacion;

                $accion = $detalle !== null
                    ? 'Ticket cerrado (' . $detalle->detalle . ')' . ($observacion !== null ? ': ' . $observacion : '')
                    : 'Ticket cerrado: ' . $observacion;

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'funcionario_id' => $ticket->funcionario_id,
                    'accion' => Str::limit($accion, 250),
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);
            });

            LoggerService::info('Ticket cerrado', [
                'ticket_id' => $ticket->id,
                'detalle_id' => $detalleSeleccionadoId,
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
            ->with('success', 'Ticket cerrado exitosamente.');
    }
}
