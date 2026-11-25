<?php

namespace Tests\Feature;

use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use App\Services\Informes\InformePdfService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InformeWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function crearUsuario(int $rolId, string $nombre = 'Usuario Test'): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'name' => $nombre,
            'rol_id' => $rolId,
            'estado_user' => 1,
        ]));
    }

    private function crearFuncionario(string $nombre = 'Funcionario Test'): funcionarios
    {
        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Test'])
        );

        return funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => $nombre,
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));
    }

    public function test_cerrar_ticket_registra_ticket_id_y_tecnico_en_informe_y_pdf_lo_lee_directamente(): void
    {
        $admin = $this->crearUsuario(rol::ADMIN, 'Admin Cerrador');
        $tecnico = $this->crearUsuario(rol::TECNICO_TERCERIZADO, 'Carlos Técnico');
        $funcionario = $this->crearFuncionario('Lucía Solicitante');

        $ticket = Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => 'Internet',
            'descripcion_problema' => 'Sin acceso a red en oficina',
            'estado' => Ticket::ESTADO_ASIGNADO,
            'asignado_a' => $tecnico->id,
            'fecha_creacion' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->post(route('tickets.cerrar', $ticket), [
                'solucion_id' => 'otro',
                'observacion' => 'Se reconectó el cable de red.',
            ])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('success');

        $informe = informes::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($informe);
        $this->assertSame($ticket->id, $informe->ticket_id);
        $this->assertSame($tecnico->id, $informe->user_id);
        $this->assertSame($informe->id, $ticket->fresh()->informe?->id);
        $this->assertSame($ticket->id, $informe->ticket?->id);

        // El PDF debe leer el técnico asignado al informe, sin fallback al usuario que descarga
        $datosPdf = app(InformePdfService::class)->obtenerDatos($informe->fresh());
        $this->assertSame('Carlos Técnico', $datosPdf['tecnico']);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('informes.show', $informe), false);

        $this->actingAs($admin)
            ->get(route('informes.show', $informe))
            ->assertOk()
            ->assertSee('Carlos Técnico')
            ->assertSee(route('tickets.show', $ticket->id), false);
    }

    public function test_crear_informe_manual_guarda_user_id_del_tecnico_y_ticket_id_nulo(): void
    {
        $tecnico = $this->crearUsuario(rol::TECNICO_TERCERIZADO, 'Ana Técnica');
        $funcionario = $this->crearFuncionario();

        $this->actingAs($tecnico)
            ->post(route('informes.store'), [
                'titulo' => 'Mantenimiento preventivo de impresora',
                'funcionario_id' => $funcionario->id,
                'tipo_problema' => 'Impresora/Escáner',
                'detalle_id' => 'otro',
                'observacion' => 'Se limpiaron los rodillos.',
            ])
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('success');

        $informe = informes::where('titulo', 'Mantenimiento preventivo de impresora')->first();
        $this->assertNotNull($informe);
        $this->assertNull($informe->ticket_id);
        $this->assertSame($tecnico->id, $informe->user_id);
    }

    public function test_informe_de_ticket_limita_edicion_y_bloquea_eliminacion(): void
    {
        $admin = $this->crearUsuario(rol::ADMIN, 'Admin');
        $tecnico = $this->crearUsuario(rol::TECNICO_TERCERIZADO, 'Técnico');
        $funcionario1 = $this->crearFuncionario('Funcionario Uno');
        $funcionario2 = $this->crearFuncionario('Funcionario Dos');

        $ticket = Ticket::create([
            'funcionario_id' => $funcionario1->id,
            'tipo_problema' => 'Consulta',
            'descripcion_problema' => 'Consulta inicial',
            'estado' => Ticket::ESTADO_CERRADO,
            'asignado_a' => $tecnico->id,
            'fecha_creacion' => now()->toDateString(),
            'fecha_cierre' => now()->toDateString(),
        ]);

        $informeDeTicket = informes::withoutEvents(fn (): informes => informes::create([
            'ticket_id' => $ticket->id,
            'user_id' => $tecnico->id,
            'titulo' => 'Consulta inicial',
            'funcionario_id' => $funcionario1->id,
            'tipo_problema' => 'Consulta',
            'detalle_id' => null,
            'observacion' => 'Respondida.',
        ]));

        // Intento de cambiar funcionario_id -> bloqueado
        $this->actingAs($admin)
            ->put(route('informes.update', $informeDeTicket), [
                'titulo' => 'Cambio no permitido',
                'funcionario_id' => $funcionario2->id,
                'observacion' => 'Intento.',
            ])
            ->assertRedirect(route('informes.edit', $informeDeTicket))
            ->assertSessionHas('error');

        // Intento de cambiar tipo_problema -> bloqueado
        $this->actingAs($admin)
            ->put(route('informes.update', $informeDeTicket), [
                'titulo' => 'Cambio no permitido',
                'tipo_problema' => 'Internet',
                'observacion' => 'Intento.',
            ])
            ->assertRedirect(route('informes.edit', $informeDeTicket))
            ->assertSessionHas('error');

        // Edición permitida (solo título/detalle/observación) -> funciona conservando funcionario y tipo
        $this->actingAs($admin)
            ->put(route('informes.update', $informeDeTicket), [
                'titulo' => 'Consulta aclarada',
                'detalle_id' => 'otro',
                'observacion' => 'Se explicó el procedimiento.',
            ])
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('success');

        $informeDeTicket->refresh();
        $this->assertSame('Consulta aclarada', $informeDeTicket->titulo);
        $this->assertSame($funcionario1->id, $informeDeTicket->funcionario_id);
        $this->assertSame('Consulta', $informeDeTicket->tipo_problema);

        // Eliminación de informe vinculado a ticket -> bloqueada
        $this->actingAs($admin)
            ->delete(route('informes.destroy', $informeDeTicket))
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('error');
        $this->assertModelExists($informeDeTicket);

        // Informe manual (sin ticket_id) sí se puede eliminar por Admin IT
        $informeManual = informes::withoutEvents(fn (): informes => informes::create([
            'ticket_id' => null,
            'user_id' => $tecnico->id,
            'titulo' => 'Informe manual borrable',
            'funcionario_id' => $funcionario1->id,
            'tipo_problema' => 'Consulta',
            'detalle_id' => null,
            'observacion' => 'Manual.',
        ]));

        $this->actingAs($admin)
            ->delete(route('informes.destroy', $informeManual))
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('success');
        $this->assertModelMissing($informeManual);
    }

    public function test_no_se_puede_eliminar_detalle_usado_en_informes_o_tickets_y_fk_restringe_borrado(): void
    {
        $admin = $this->crearUsuario(rol::ADMIN, 'Admin Detalles');
        $funcionario = $this->crearFuncionario('Funcionario Detalles');

        $detalleEnInforme = detalles_problemas::withoutEvents(fn (): detalles_problemas => detalles_problemas::create([
            'tipo_problema' => 'Internet',
            'detalle' => 'Cable de red desconectado',
        ]));

        informes::withoutEvents(fn (): informes => informes::create([
            'ticket_id' => null,
            'user_id' => $admin->id,
            'titulo' => 'Fallo de red',
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => 'Internet',
            'detalle_id' => $detalleEnInforme->id,
            'observacion' => null,
        ]));

        $this->actingAs($admin)
            ->delete(route('detalles-problemas.destroy', $detalleEnInforme))
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('error');
        $this->assertModelExists($detalleEnInforme);

        $detalleEnTicket = detalles_problemas::withoutEvents(fn (): detalles_problemas => detalles_problemas::create([
            'tipo_problema' => 'Internet',
            'detalle' => 'Puerto de switch dañado',
        ]));

        Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => 'Internet',
            'detalles_id' => $detalleEnTicket->id,
            'descripcion_problema' => 'Sin señal en oficina',
            'estado' => Ticket::ESTADO_CERRADO,
            'asignado_a' => $admin->id,
            'fecha_creacion' => now()->toDateString(),
            'fecha_cierre' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->delete(route('detalles-problemas.destroy', $detalleEnTicket))
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('error');
        $this->assertModelExists($detalleEnTicket);

        // La FK restrictOnDelete también impide el borrado directo en BD
        $this->expectException(QueryException::class);
        detalles_problemas::withoutEvents(fn () => $detalleEnTicket->delete());
    }

    public function test_se_puede_eliminar_detalle_sin_uso(): void
    {
        $admin = $this->crearUsuario(rol::ADMIN, 'Admin Detalles Libre');

        $detalleSinUso = detalles_problemas::withoutEvents(fn (): detalles_problemas => detalles_problemas::create([
            'tipo_problema' => 'Consulta',
            'detalle' => 'Detalle temporal sin uso',
        ]));

        $this->actingAs($admin)
            ->delete(route('detalles-problemas.destroy', $detalleSinUso))
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($detalleSinUso);
    }

    public function test_eliminar_funcionario_o_usuario_con_tickets_informes_o_historial_es_restringido_por_fk(): void
    {
        $tecnicoTicket = $this->crearUsuario(rol::TECNICO_TERCERIZADO, 'Técnico Ticket FK');
        $tecnicoInforme = $this->crearUsuario(rol::TECNICO_TERCERIZADO, 'Técnico Informe FK');
        $funcionarioTicket = $this->crearFuncionario('Funcionario Ticket FK');
        $funcionarioInforme = $this->crearFuncionario('Funcionario Informe FK');
        $funcionarioHistorial = $this->crearFuncionario('Funcionario Historial FK');

        $ticket = Ticket::create([
            'funcionario_id' => $funcionarioTicket->id,
            'tipo_problema' => 'Internet',
            'descripcion_problema' => 'Prueba FK restrict',
            'estado' => Ticket::ESTADO_ASIGNADO,
            'asignado_a' => $tecnicoTicket->id,
            'fecha_creacion' => now()->toDateString(),
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'funcionario_id' => $funcionarioHistorial->id,
            'accion' => 'Ticket creado',
            'responsable_id' => null,
            'fecha' => now()->toDateString(),
        ]);

        informes::withoutEvents(fn (): informes => informes::create([
            'ticket_id' => null,
            'user_id' => $tecnicoInforme->id,
            'titulo' => 'Informe FK restrict',
            'funcionario_id' => $funcionarioInforme->id,
            'tipo_problema' => 'Internet',
            'detalle_id' => null,
            'observacion' => 'Observación.',
        ]));

        foreach ([$funcionarioTicket, $funcionarioInforme, $funcionarioHistorial, $tecnicoTicket, $tecnicoInforme] as $registroProtegido) {
            try {
                $registroProtegido::withoutEvents(fn () => $registroProtegido->delete());
                $this->fail('Se esperaba QueryException por restrictOnDelete en '.$registroProtegido::class);
            } catch (QueryException $e) {
                $this->assertSame('23000', (string) $e->getCode());
            }
        }
    }
}
