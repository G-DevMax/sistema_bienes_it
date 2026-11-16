<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
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

    public function show(Ticket $ticket): View
    {
        $ticket->load(['funcionario.dependencia', 'tecnicoAsignado', 'historial.responsable']);
        $tecnicos = User::where('estado_user', 1)->orderBy('name')->get();

        return view('Tickets.show', compact('ticket', 'tecnicos'));
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

        $datos = $request->validate([
            'observacion' => ['nullable', 'string', 'max:2000'],
        ], [
            'observacion.max' => 'La observación no puede superar los 2000 caracteres.',
        ]);

        $observacion = isset($datos['observacion']) && trim($datos['observacion']) !== ''
            ? trim($datos['observacion'])
            : null;

        try {
            DB::transaction(function () use ($ticket, $observacion): void {
                $ticket->update([
                    'estado' => Ticket::ESTADO_CERRADO,
                    'fecha_cierre' => now()->toDateString(),
                    'observacion' => $observacion,
                ]);

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'funcionario_id' => $ticket->funcionario_id,
                    'accion' => $observacion !== null
                        ? Str::limit('Ticket cerrado: '.$observacion, 250)
                        : 'Ticket cerrado',
                    'responsable_id' => Auth::id(),
                    'fecha' => now()->toDateString(),
                ]);
            });

            LoggerService::info('Ticket cerrado', [
                'ticket_id' => $ticket->id,
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
