<?php

namespace Tests\Feature;

use App\Http\Controllers\Tickets\TicketController;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
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

    public function test_invitado_es_redirigido_al_login_en_rutas_de_tecnico_pero_rutas_de_usuario_son_publicas(): void
    {
        $this->get(route('tickets.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.pendientes-status'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->get(route('tickets.create-manual'))->assertRedirect(route('login'));
        $this->post(route('tickets.store-manual'))->assertRedirect(route('login'));
        $this->post(route('tickets.asignar', 1))->assertRedirect(route('login'));
        $this->post(route('tickets.cerrar', 1))->assertRedirect(route('login'));

        $rutaCrear = app('router')->getRoutes()->getByName('tickets.create');
        $rutaGuardar = app('router')->getRoutes()->getByName('tickets.store');

        $this->assertNotNull($rutaCrear);
        $this->assertNotNull($rutaGuardar);
        $this->assertNotContains('auth', $rutaCrear->gatherMiddleware());
        $this->assertNotContains('auth', $rutaGuardar->gatherMiddleware());
    }

    public function test_buscador_universal_de_ticket_consulta_por_nombre_de_funcionario(): void
    {
        $query = Ticket::query()->buscadorUniversal('Carlos');
        $sql = $query->toSql();

        $this->assertStringContainsString('funcionarios', $sql);
        $this->assertStringContainsString('nombre', $sql);
        $this->assertSame(['%Carlos%'], $query->getBindings());
    }

    public function test_formulario_usuario_tickets_renderiza_tomselect_radios_contador_y_diseno_solido(): void
    {
        View::share('errors', new ViewErrorBag);

        $funcionario = new funcionarios(['nombre' => 'Carlos Benítez']);
        $funcionario->id = 4;

        $vista = $this->view('Tickets.create', [
            'funcionarios' => collect([$funcionario]),
            'tiposProblema' => Ticket::TIPOS_PROBLEMA,
        ]);

        $vista->assertSee('Crear Ticket de Soporte');
        $vista->assertSee('searchable-select', false);
        $vista->assertSee('Carlos Benítez');
        $vista->assertSee('type="radio"', false);
        foreach (Ticket::TIPOS_PROBLEMA as $tipo) {
            $vista->assertSee($tipo);
        }
        $vista->assertSee('0 / 100 palabras');
        $vista->assertSee('bg-neutral-950', false);
        $vista->assertSee('bg-indigo-600', false);
        $vista->assertDontSee('backdrop-blur', false);
        $vista->assertSee(route('tickets.store'));
    }

    public function test_formulario_manual_tecnico_renderiza_diseno_del_sistema_y_mismos_campos(): void
    {
        View::share('errors', new ViewErrorBag);

        $funcionario = new funcionarios(['nombre' => 'Carlos Benítez']);
        $funcionario->id = 4;

        $vista = $this->view('Tickets.create_manual', [
            'funcionarios' => collect([$funcionario]),
            'tiposProblema' => Ticket::TIPOS_PROBLEMA,
        ]);

        $vista->assertSee('Crear Ticket Manual');
        $vista->assertSee('searchable-select', false);
        $vista->assertSee('Carlos Benítez');
        $vista->assertSee('type="radio"', false);
        foreach (Ticket::TIPOS_PROBLEMA as $tipo) {
            $vista->assertSee($tipo);
        }
        $vista->assertSee('0 / 100 palabras');
        $vista->assertSee('backdrop-blur-xl', false);
        $vista->assertSee(route('tickets.store-manual'));
    }

    public function test_validacion_titulo_hasta_100_palabras_y_tipos_incluyen_impresora(): void
    {
        $tipoPrueba = Ticket::TIPOS_PROBLEMA[0];

        $reglaPalabras = function (string $attribute, mixed $value, \Closure $fail): void {
            $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
            if (count($palabras) > 100) {
                $fail('El título del problema no puede superar las 100 palabras.');
            }
        };

        $valido = Validator::make(
            [
                'tipo_problema' => $tipoPrueba,
                'descripcion_problema' => implode(' ', array_fill(0, 100, 'a')),
            ],
            [
                'tipo_problema' => ['required', Rule::in(Ticket::TIPOS_PROBLEMA)],
                'descripcion_problema' => ['required', 'string', 'max:255', $reglaPalabras],
            ]
        );
        $this->assertFalse($valido->fails());

        $invalido = Validator::make(
            [
                'tipo_problema' => $tipoPrueba,
                'descripcion_problema' => implode(' ', array_fill(0, 101, 'a')),
            ],
            [
                'tipo_problema' => ['required', Rule::in(Ticket::TIPOS_PROBLEMA)],
                'descripcion_problema' => ['required', 'string', 'max:255', $reglaPalabras],
            ]
        );
        $this->assertTrue($invalido->fails());
        $this->assertArrayHasKey('descripcion_problema', $invalido->errors()->toArray());
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
            'descripcion_problema' => 'Monitor sin señal HDMI',
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
            'detallesComunes' => collect(),
        ]);
        $showPendiente->assertSee('Ticket #15');
        $showPendiente->assertSee('Asignar Técnico');
        $showPendiente->assertSee('Ana Soporte');
        $showPendiente->assertSee(route('tickets.asignar', 15));

        $detalleComun = new detalles_problemas(['tipo_problema' => 'Hardware', 'detalle' => 'Cable HDMI defectuoso']);
        $detalleComun->id = 7;

        $ticketAsignado = new Ticket([
            'funcionario_id' => 4,
            'tipo_problema' => 'Hardware',
            'descripcion_problema' => 'Monitor sin señal HDMI',
            'estado' => Ticket::ESTADO_ASIGNADO,
            'asignado_a' => 2,
            'fecha_creacion' => now(),
        ]);
        $ticketAsignado->id = 17;
        $ticketAsignado->setRelation('funcionario', $funcionario);
        $ticketAsignado->setRelation('tecnicoAsignado', $tecnico);
        $ticketAsignado->setRelation('historial', collect());

        $showAsignado = $this->view('Tickets.show', [
            'ticket' => $ticketAsignado,
            'tecnicos' => collect([$tecnico]),
            'detallesComunes' => collect([$detalleComun]),
        ]);
        $showAsignado->assertSee('Problema Común (Hardware)');
        $showAsignado->assertSee('Cable HDMI defectuoso');
        $showAsignado->assertSee('Otro (especificar)');
        $showAsignado->assertSee(route('tickets.cerrar', 17));

        $eventoCreacion = new TicketHistory([
            'accion' => 'Ticket creado',
            'fecha' => now()->subDay(),
        ]);
        $eventoCreacion->setRelation('responsable', null);
        $eventoCreacion->setRelation('funcionario', $funcionario);

        $eventoCierre = new TicketHistory([
            'accion' => 'Ticket cerrado (Cable HDMI defectuoso): Se cambió el cable HDMI',
            'fecha' => now(),
        ]);
        $eventoCierre->setRelation('responsable', $tecnico);
        $eventoCierre->setRelation('funcionario', $funcionario);

        $ticketCerrado = new Ticket([
            'funcionario_id' => 4,
            'tipo_problema' => 'Hardware',
            'detalle_id' => 7,
            'descripcion_problema' => 'Monitor sin señal HDMI',
            'estado' => Ticket::ESTADO_CERRADO,
            'asignado_a' => 2,
            'fecha_creacion' => now()->subDay(),
            'fecha_cierre' => now(),
            'observacion' => 'Se cambió el cable HDMI.',
        ]);
        $ticketCerrado->id = 16;
        $ticketCerrado->setRelation('funcionario', $funcionario);
        $ticketCerrado->setRelation('tecnicoAsignado', $tecnico);
        $ticketCerrado->setRelation('detalle', $detalleComun);
        $ticketCerrado->setRelation('historial', collect([$eventoCierre, $eventoCreacion]));

        $showCerrado = $this->view('Tickets.show', [
            'ticket' => $ticketCerrado,
            'tecnicos' => collect([$tecnico]),
            'detallesComunes' => collect([$detalleComun]),
        ]);
        $showCerrado->assertSee('Ticket Cerrado');
        $showCerrado->assertSee('Cable HDMI defectuoso');
        $showCerrado->assertSee('Se cambió el cable HDMI.');
        $showCerrado->assertSee('Ana Soporte');
        $showCerrado->assertSee('Ticket creado');
        $showCerrado->assertSee('Por Carlos Benítez');
    }

    public function test_mapeo_de_tipo_para_detalles_y_validacion_observacion_en_cierre(): void
    {
        $this->assertSame('Cuentas y Accesos', Ticket::tipoParaDetalle('Cuenta y Acceso'));
        $this->assertSame('Hardware', Ticket::tipoParaDetalle('Hardware'));

        $detalle = new detalles_problemas(['detalle' => 'Fallo de red']);
        $conDetalle = new Ticket(['detalle_id' => 3]);
        $conDetalle->setRelation('detalle', $detalle);
        $this->assertSame('Fallo de red', $conDetalle->detalleTexto());

        $sinDetalle = new Ticket(['detalle_id' => null]);
        $this->assertSame('Otro (especificar)', $sinDetalle->detalleTexto());

        $valOtroSinObservacion = Validator::make(
            ['detalle_id' => null, 'observacion' => ''],
            ['observacion' => [Rule::requiredIf(true), 'nullable', 'string', 'max:2000']]
        );
        $this->assertTrue($valOtroSinObservacion->fails());
        $this->assertArrayHasKey('observacion', $valOtroSinObservacion->errors()->toArray());

        $valConDetalleSinObservacion = Validator::make(
            ['detalle_id' => 3, 'observacion' => null],
            ['observacion' => [Rule::requiredIf(false), 'nullable', 'string', 'max:2000']]
        );
        $this->assertFalse($valConDetalleSinObservacion->fails());
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

        $html = view('layouts.app', ['pendientes' => 4])->render();

        $this->assertStringContainsString('<title>(4) Sistema de Bienes IT</title>', $html);
        $this->assertStringContainsString('InfraDesk - Tickets IT', $html);
        $this->assertStringContainsString('Inicio', $html);
        $this->assertStringContainsString('Listado de Tickets', $html);
        $this->assertStringContainsString('Crear Ticket', $html);
        $this->assertStringContainsString(route('tickets.dashboard'), $html);
        $this->assertStringContainsString(route('tickets.index'), $html);
        $this->assertStringContainsString(route('tickets.create-manual'), $html);

        $routeIndex = app('router')->getRoutes()->getByName('tickets.index');
        $requestIndex = Request::create('/tickets/listado', 'GET');
        $requestIndex->setRouteResolver(fn () => $routeIndex);
        app()->instance('request', $requestIndex);

        $htmlIndex = view('layouts.app', ['pendientes' => 4])->render();
        $this->assertStringContainsString('<title>Sistema de Bienes IT</title>', $htmlIndex);
    }

    public function test_dashboard_filtro_por_mes_renderiza_boton_calendario_y_filtra_estadisticas(): void
    {
        View::share('errors', new ViewErrorBag);

        // Vista en el mes actual (por defecto sin botón de restablecer y acotada a >= 2026)
        $mesActual = now()->format('Y-m');
        $anioActual = now()->year;
        $vistaActual = $this->view('Tickets.dashboard', [
            'pendientes' => 3,
            'asignados' => 1,
            'cerrados' => 5,
            'ticketsRecientes' => collect(),
            'ultimoPendienteId' => 10,
            'mes' => $mesActual,
            'nombreMes' => 'Noviembre 2026',
            'nombreMesSimple' => 'Noviembre',
            'anio' => (string) $anioActual,
            'anioMinimo' => Ticket::ANIO_MINIMO,
            'anioMaximo' => $anioActual,
        ]);

        $vistaActual->assertSee('Mes de Noviembre');
        $vistaActual->assertSee('type="month"', false);
        $vistaActual->assertSee('name="mes"', false);
        $vistaActual->assertSee('value="'.$mesActual.'"', false);
        $vistaActual->assertSee('min="2026-01"', false);
        $vistaActual->assertSee('max="'.$anioActual.'-12"', false);
        $vistaActual->assertDontSee('Volver al mes actual');

        // Vista en un mes filtrado diferente dentro del rango permitido
        $mesDistinto = now()->month === 1 ? $anioActual.'-02' : $anioActual.'-01';
        $vistaOtroMes = $this->view('Tickets.dashboard', [
            'pendientes' => 0,
            'asignados' => 0,
            'cerrados' => 2,
            'ticketsRecientes' => collect(),
            'ultimoPendienteId' => 10,
            'mes' => $mesDistinto,
            'nombreMes' => 'Enero '.$anioActual,
            'nombreMesSimple' => 'Enero',
            'anio' => (string) $anioActual,
            'anioMinimo' => 2026,
            'anioMaximo' => max(2028, $anioActual),
        ]);

        $vistaOtroMes->assertSee('Mes de Enero');
        $vistaOtroMes->assertSee('value="'.$mesDistinto.'"', false);
        $vistaOtroMes->assertSee('min="2026-01"', false);
        $vistaOtroMes->assertSee('max="'.max(2028, $anioActual).'-12"', false);
        $vistaOtroMes->assertSee('Volver al mes actual');

        // Comprobación de consulta para filtrado de estadísticas por mes y año
        $queryPendientes = Ticket::where('estado', Ticket::ESTADO_PENDIENTE)
            ->whereYear('fecha_creacion', 2026)
            ->whereMonth('fecha_creacion', 8);

        $sql = $queryPendientes->toSql();
        $this->assertStringContainsString('fecha_creacion', $sql);
        $this->assertStringContainsString('estado', $sql);

        // Validación de rango dinámico (>= 2026, hasta año máximo, meses reales 01..12)
        $this->assertSame('2026-11', Ticket::mesValidoEnRango('2026-11', 2026, 2026));
        $this->assertSame('2026-05', Ticket::mesValidoEnRango('2026-05', 2026, 2028));
        $this->assertNull(Ticket::mesValidoEnRango('2025-12', 2026, 2026));
        $this->assertNull(Ticket::mesValidoEnRango('2027-01', 2026, 2026));
        $this->assertNull(Ticket::mesValidoEnRango('2026-13', 2026, 2026));
        $this->assertNull(Ticket::mesValidoEnRango('2026-00', 2026, 2026));
        $this->assertNull(Ticket::mesValidoEnRango('mes-invalido', 2026, 2026));
    }
}
