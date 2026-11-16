<?php

namespace Tests\Feature;

use App\Http\Controllers\Tickets\TicketController;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class TicketTest extends TestCase
{
    public function test_welcome_enlaza_al_dashboard_de_tickets(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('tickets.dashboard'));
        $response->assertSee('Tickets');
    }

    public function test_invitado_es_redirigido_al_login_en_rutas_de_tickets(): void
    {
        $this->get(route('tickets.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->post(route('tickets.asignar', 1))->assertRedirect(route('login'));
        $this->post(route('tickets.cerrar', 1))->assertRedirect(route('login'));
    }

    public function test_buscador_universal_de_ticket_consulta_por_nombre_de_funcionario(): void
    {
        $query = Ticket::query()->buscadorUniversal('Carlos');
        $sql = $query->toSql();

        $this->assertStringContainsString('funcionarios', $sql);
        $this->assertStringContainsString('nombre', $sql);
        $this->assertSame(['%Carlos%'], $query->getBindings());
    }

    public function test_vistas_de_tickets_renderizan_dashboard_listado_y_detalle(): void
    {
        View::share('errors', new ViewErrorBag);

        $dependencia = new dependencias(['nombre' => 'Secretaría General']);
        $funcionario = new funcionarios(['nombre' => 'Carlos Benítez']);
        $funcionario->id = 4;
        $funcionario->setRelation('dependencia', $dependencia);

        $tecnico = new User(['name' => 'Ana Soporte', 'email' => 'ana@example.com']);
        $tecnico->id = 2;

        $ticketPendiente = new Ticket([
            'funcionario_id' => 4,
            'tipo_problema' => 'Hardware',
            'descripcion_problea' => 'Monitor sin señal HDMI',
            'estado' => Ticket::ESTADO_PENDIENTE,
            'fecha_creacion' => now(),
        ]);
        $ticketPendiente->id = 15;
        $ticketPendiente->created_at = now();
        $ticketPendiente->setRelation('funcionario', $funcionario);
        $ticketPendiente->setRelation('tecnicoAsignado', null);
        $ticketPendiente->setRelation('historial', collect());

        $dashboard = $this->view('Tickets.dashboard', [
            'pendientes' => 3,
            'asignados' => 2,
            'cerrados' => 7,
            'ticketsRecientes' => collect([$ticketPendiente]),
        ]);
        $dashboard->assertSee('Tickets pendientes');
        $dashboard->assertSee('Tickets Asignados');
        $dashboard->assertSee('Tickets Cerrados');
        $dashboard->assertSee('Tickets recientes');
        $dashboard->assertSee('Carlos Benítez');
        $dashboard->assertSee('Monitor sin señal HDMI');
        $dashboard->assertSee('Pendiente');

        $paginador = new LengthAwarePaginator(collect([$ticketPendiente]), 1, 10, 1);
        $index = $this->view('Tickets.index', [
            'tickets' => $paginador,
            'tiposProblema' => Ticket::TIPOS_PROBLEMA,
        ]);
        $index->assertSee('Listado de Tickets');
        $index->assertSee('#15');
        $index->assertSee('Carlos Benítez');
        $index->assertSee('Hardware');
        $index->assertSee('Monitor sin señal HDMI');
        $index->assertSee(route('tickets.show', 15));

        $showPendiente = $this->view('Tickets.show', [
            'ticket' => $ticketPendiente,
            'tecnicos' => collect([$tecnico]),
        ]);
        $showPendiente->assertSee('Ticket #15');
        $showPendiente->assertSee('Asignar Técnico');
        $showPendiente->assertSee('Ana Soporte');
        $showPendiente->assertSee(route('tickets.asignar', 15));

        $evento = new TicketHistory([
            'accion' => 'Ticket cerrado: Se cambió el cable HDMI',
            'fecha' => now(),
        ]);
        $evento->setRelation('responsable', $tecnico);

        $ticketCerrado = new Ticket([
            'funcionario_id' => 4,
            'tipo_problema' => 'Hardware',
            'descripcion_problea' => 'Monitor sin señal HDMI',
            'estado' => Ticket::ESTADO_CERRADO,
            'asignado_a' => 2,
            'fecha_creacion' => now()->subDay(),
            'fecha_cierre' => now(),
            'observacion' => 'Se cambió el cable HDMI.',
        ]);
        $ticketCerrado->id = 16;
        $ticketCerrado->setRelation('funcionario', $funcionario);
        $ticketCerrado->setRelation('tecnicoAsignado', $tecnico);
        $ticketCerrado->setRelation('historial', collect([$evento]));

        $showCerrado = $this->view('Tickets.show', [
            'ticket' => $ticketCerrado,
            'tecnicos' => collect([$tecnico]),
        ]);
        $showCerrado->assertSee('Ticket Cerrado');
        $showCerrado->assertSee('Se cambió el cable HDMI.');
        $showCerrado->assertSee('Ana Soporte');
    }

    public function test_controlador_impide_asignar_cerrado_y_cerrar_pendiente(): void
    {
        $controller = new TicketController;

        $cerrado = new Ticket(['estado' => Ticket::ESTADO_CERRADO]);
        $cerrado->id = 99;

        $respAsignar = $controller->asignar(Request::create('/', 'POST', ['asignado_a' => 1]), $cerrado);
        $this->assertSame(route('tickets.show', $cerrado), $respAsignar->getTargetUrl());
        $this->assertTrue(session()->has('error'));

        session()->forget('error');

        $pendiente = new Ticket(['estado' => Ticket::ESTADO_PENDIENTE]);
        $pendiente->id = 100;

        $respCerrar = $controller->cerrar(Request::create('/', 'POST', []), $pendiente);
        $this->assertSame(route('tickets.show', $pendiente), $respCerrar->getTargetUrl());
        $this->assertTrue(session()->has('error'));
    }

    public function test_navbar_tickets_muestra_inicio_listado_y_crear_ticket_en_layout(): void
    {
        $tecnico = new User(['name' => 'Tecnico', 'email' => 'tec@example.com']);
        $tecnico->id = 1;
        $this->be($tecnico);

        $route = app('router')->getRoutes()->getByName('tickets.dashboard');
        $request = Request::create('/tickets', 'GET');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        $html = view('layouts.app')->render();

        $this->assertStringContainsString('InfraDesk - Tickets IT', $html);
        $this->assertStringContainsString('Inicio', $html);
        $this->assertStringContainsString('Listado de Tickets', $html);
        $this->assertStringContainsString('Crear Ticket', $html);
        $this->assertStringContainsString(route('tickets.dashboard'), $html);
        $this->assertStringContainsString(route('tickets.index'), $html);
    }
}
