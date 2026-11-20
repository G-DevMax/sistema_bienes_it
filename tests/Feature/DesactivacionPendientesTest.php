<?php

namespace Tests\Feature;

use App\Enums\TipoProblema;
use App\Models\Bienes\categorias;
use App\Models\Bienes\estados;
use App\Models\Bienes\items;
use App\Models\Bienes\movimientos;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DesactivacionPendientesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_no_permite_desactivar_funcionario_con_prestamo_o_transferencia_pendiente(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
        $this->actingAs($admin);

        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia M5'])
        );
        $categoria = categorias::first() ?? categorias::withoutEvents(
            fn (): categorias => categorias::create(['nombre' => 'Categoria M5'])
        );

        $item = items::withoutEvents(fn (): items => items::create([
            'categoria_id' => $categoria->id,
            'descripcion' => 'Notebook de prueba M5',
            'codigo_patrimonial' => 'M5-PAT-'.uniqid(),
            'stock_total' => 2,
            'stock_disponible' => 1,
            'estado_id' => estados::NO_APLICA,
        ]));

        $funcionarioConPrestamo = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Prestamo M5',
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        $prestamo = movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionarioConPrestamo->id,
            'user_id' => $admin->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => now(),
            'fecha_devolucion_prevista' => now()->addDays(5)->toDateString(),
            'cantidad' => 1,
        ]));

        // DELETE bloqueado con el mensaje modal exacto
        $this->delete(route('funcionarios.destroy', $funcionarioConPrestamo->id))
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('error', funcionarios::MENSAJE_PENDIENTES)
            ->assertSessionHas('advertencia_pendientes', funcionarios::MENSAJE_PENDIENTES);

        $this->assertSame(1, (int) $funcionarioConPrestamo->fresh()->estado_funcionario);

        // El listado renderiza el modal de advertencia para funcionarios con pendientes
        $this->get(route('funcionarios.index'))
            ->assertOk()
            ->assertSee('modalAdvertenciaPendientes', false)
            ->assertSee(funcionarios::MENSAJE_PENDIENTES);

        // PUT con estado_funcionario = 0 también bloqueado
        $this->put(route('funcionarios.update', $funcionarioConPrestamo->id), [
            'nombre' => $funcionarioConPrestamo->nombre,
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => '0',
        ])
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('advertencia_pendientes', funcionarios::MENSAJE_PENDIENTES);

        $this->assertSame(1, (int) $funcionarioConPrestamo->fresh()->estado_funcionario);

        // Al devolver el préstamo, ya permite desactivar
        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionarioConPrestamo->id,
            'user_id' => $admin->id,
            'tipo_movimiento' => movimientos::DEVOLUCION,
            'movimiento_origen_id' => $prestamo->id,
            'fecha_movimiento' => now(),
            'cantidad' => 1,
        ]));

        $this->delete(route('funcionarios.destroy', $funcionarioConPrestamo->id))
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $funcionarioConPrestamo->fresh()->estado_funcionario);

        // Transferencia pendiente también bloquea desactivación vía PUT
        $funcionarioConTransferencia = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Transferencia M5',
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionarioConTransferencia->id,
            'user_id' => $admin->id,
            'tipo_movimiento' => movimientos::TRANSFERENCIA,
            'fecha_movimiento' => now(),
            'cantidad' => 1,
        ]));

        $this->put(route('funcionarios.update', $funcionarioConTransferencia->id), [
            'nombre' => $funcionarioConTransferencia->nombre,
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => '0',
        ])
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('error', funcionarios::MENSAJE_PENDIENTES);

        $this->assertSame(1, (int) $funcionarioConTransferencia->fresh()->estado_funcionario);
    }

    public function test_desactivar_tecnico_con_ticket_asignado_exige_reasignar_y_luego_desactiva(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
        $tecnicoOrigen = User::withoutEvents(fn (): User => User::factory()->create([
            'name' => 'Tecnico Origen M5',
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 1,
        ]));
        $tecnicoDestino = User::withoutEvents(fn (): User => User::factory()->create([
            'name' => 'Tecnico Destino M5',
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 1,
        ]));
        $tecnicoInactivo = User::withoutEvents(fn (): User => User::factory()->create([
            'name' => 'Tecnico Inactivo M5',
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 0,
        ]));
        $supervisor = User::withoutEvents(fn (): User => User::factory()->create([
            'name' => 'Supervisor No Tecnico M5',
            'rol_id' => rol::SUPERVISOR,
            'estado_user' => 1,
        ]));
        $this->actingAs($admin);

        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Tecnico M5'])
        );
        $funcionario = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Ticket M5',
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        $ticketAsignado = Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => TipoProblema::values()[0],
            'descripcion_problema' => 'Ticket abierto asignado M5',
            'estado' => Ticket::ESTADO_ASIGNADO,
            'asignado_a' => $tecnicoOrigen->id,
            'fecha_creacion' => now()->toDateString(),
        ]);

        $ticketCerrado = Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => TipoProblema::values()[0],
            'descripcion_problema' => 'Ticket ya cerrado M5',
            'estado' => Ticket::ESTADO_CERRADO,
            'asignado_a' => $tecnicoOrigen->id,
            'fecha_creacion' => now()->subDay()->toDateString(),
            'fecha_cierre' => now()->toDateString(),
        ]);

        // El listado de usuarios incluye el modal de reasignación y detecta el ticket asignado
        $this->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('modalReasignarTickets', false)
            ->assertSee('abrirModalReasignar', false);

        // Sin reasignar_a: DELETE bloqueado
        $this->delete(route('usuarios.destroy', $tecnicoOrigen->id))
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error')
            ->assertSessionHas('reasignar_usuario_id', $tecnicoOrigen->id);

        $this->assertSame(1, (int) $tecnicoOrigen->fresh()->estado_user);
        $this->assertSame($tecnicoOrigen->id, (int) $ticketAsignado->fresh()->asignado_a);

        // Sin reasignar_a: PUT con estado_user = 0 bloqueado
        $this->put(route('usuarios.update', $tecnicoOrigen->id), [
            'name' => $tecnicoOrigen->name,
            'email' => $tecnicoOrigen->email,
            'rol_id' => $tecnicoOrigen->rol_id,
            'estado_user' => '0',
        ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error')
            ->assertSessionHas('reasignar_usuario_id', $tecnicoOrigen->id);

        $this->assertSame(1, (int) $tecnicoOrigen->fresh()->estado_user);

        // Destinatario inválido (sí mismo, técnico inactivo o no técnico): bloqueado
        foreach ([$tecnicoOrigen->id, $tecnicoInactivo->id, $supervisor->id] as $destinoInvalido) {
            $this->delete(route('usuarios.destroy', $tecnicoOrigen->id), [
                'reasignar_a' => $destinoInvalido,
            ])
                ->assertRedirect(route('usuarios.index'))
                ->assertSessionHas('error')
                ->assertSessionHas('reasignar_usuario_id', $tecnicoOrigen->id);

            $this->assertSame(1, (int) $tecnicoOrigen->fresh()->estado_user);
        }

        // Con técnico activo válido: reasigna tickets en estado Asignado, registra historial y desactiva
        $tokenOriginal = $tecnicoOrigen->remember_token;

        $this->delete(route('usuarios.destroy', $tecnicoOrigen->id), [
            'reasignar_a' => $tecnicoDestino->id,
        ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $tecnicoActualizado = $tecnicoOrigen->fresh();
        $this->assertSame(0, (int) $tecnicoActualizado->estado_user);
        $this->assertNotSame($tokenOriginal, $tecnicoActualizado->remember_token);
        $this->assertSame($tecnicoDestino->id, (int) $ticketAsignado->fresh()->asignado_a);
        $this->assertSame($tecnicoOrigen->id, (int) $ticketCerrado->fresh()->asignado_a);
        $this->assertDatabaseHas('ticket_history', [
            'ticket_id' => $ticketAsignado->id,
            'funcionario_id' => $funcionario->id,
            'accion' => 'Ticket reasignado a '.$tecnicoDestino->name.' por desactivación de técnico',
            'responsable_id' => $admin->id,
        ]);

        // Técnico sin tickets en estado Asignado se puede desactivar por PUT directamente
        $tecnicoSinAbiertos = User::withoutEvents(fn (): User => User::factory()->create([
            'name' => 'Tecnico Sin Abiertos M5',
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 1,
        ]));

        $this->put(route('usuarios.update', $tecnicoSinAbiertos->id), [
            'name' => $tecnicoSinAbiertos->name,
            'email' => $tecnicoSinAbiertos->email,
            'rol_id' => $tecnicoSinAbiertos->rol_id,
            'estado_user' => '0',
        ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, (int) $tecnicoSinAbiertos->fresh()->estado_user);
    }
}
