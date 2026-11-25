<?php

namespace Tests\Feature;

use App\Enums\TipoProblema;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TicketEstadisticasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invitado_es_redirigido_al_login_y_usuario_sin_permiso_recibe_forbidden(): void
    {
        $this->get(route('tickets.estadisticas'))->assertRedirect(route('login'));
        $this->get(route('tickets.estadisticas.datos'))->assertRedirect(route('login'));

        $sinPermiso = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::FUNCIONARIO,
            'estado_user' => 1,
        ]));

        $this->actingAs($sinPermiso)
            ->get(route('tickets.estadisticas'))
            ->assertForbidden();

        $this->actingAs($sinPermiso)
            ->get(route('tickets.estadisticas.datos'))
            ->assertForbidden();
    }

    public function test_tecnico_ve_estadisticas_de_problemas_con_chartjs_y_endpoint_json(): void
    {
        $tecnico = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 1,
        ]));
        $this->actingAs($tecnico);

        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Stats Test'])
        );

        $funcionario = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Stats Problemas',
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        Ticket::whereBetween('fecha_creacion', ['2026-04-01', '2026-04-30'])->delete();

        $tipos = TipoProblema::values();
        $tipoMayor = $tipos[2]; // Internet
        $tipoMedio = $tipos[0]; // Equipo/Dispositivo

        for ($i = 0; $i < 3; $i++) {
            Ticket::create([
                'funcionario_id' => $funcionario->id,
                'tipo_problema' => $tipoMayor,
                'descripcion_problema' => 'Falla Internet '.$i,
                'estado' => Ticket::ESTADO_PENDIENTE,
                'fecha_creacion' => '2026-04-10',
            ]);
        }

        Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => $tipoMedio,
            'descripcion_problema' => 'Falla Equipo',
            'estado' => Ticket::ESTADO_ASIGNADO,
            'asignado_a' => $tecnico->id,
            'fecha_creacion' => '2026-04-20',
        ]);

        // Ticket de otro mes que no debe incluirse en el conteo de abril
        Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => $tipoMedio,
            'descripcion_problema' => 'Falla Mayo',
            'estado' => Ticket::ESTADO_PENDIENTE,
            'fecha_creacion' => '2026-05-01',
        ]);

        $response = $this->get(route('tickets.estadisticas', ['mes' => '2026-04']));

        $response->assertOk();
        $response->assertSee(route('tickets.estadisticas'));
        $response->assertSee('Estadísticas de Tickets');
        $response->assertSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertSee('<canvas id="grafico-estadisticas"', false);
        $response->assertSee("indexAxis: 'y'", false);
        $response->assertSee(json_encode(route('tickets.estadisticas.datos', ['mes' => '2026-04', 'vista' => 'problemas'])), false);
        $response->assertSee('Total del mes: 4');
        $response->assertSeeInOrder([$tipoMayor, $tipoMedio]);

        $json = $this->getJson(route('tickets.estadisticas.datos', ['mes' => '2026-04']));
        $json->assertOk()
            ->assertJson([
                'vista' => 'problemas',
                'mes' => '2026-04',
                'total_mes' => 4,
            ]);
        $this->assertSame($tipoMayor, $json->json('labels.0'));
        $this->assertSame(3, $json->json('valores.0'));
        $this->assertSame(Ticket::colorTipo($tipoMayor), $json->json('colores.0'));
        $this->assertSame($tipoMedio, $json->json('labels.1'));
        $this->assertSame(1, $json->json('valores.1'));
        $this->assertSame(Ticket::colorTipo($tipoMedio), $json->json('colores.1'));
    }

    public function test_filtro_usuarios_devuelve_top_8_en_vista_y_json_ordenados_de_mayor_a_menor(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
        $this->actingAs($admin);

        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Top8 Test'])
        );

        Ticket::whereBetween('fecha_creacion', ['2026-05-01', '2026-05-31'])->delete();

        $tipo = TipoProblema::values()[0];
        $funcionarios = [];

        for ($i = 1; $i <= 9; $i++) {
            $funcionarios[$i] = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
                'nombre' => sprintf('Usuario Ranking %02d', $i),
                'dependencia_id' => $dependencia->id,
                'estado_funcionario' => 1,
            ]));

            $cantidad = 11 - $i;
            for ($t = 0; $t < $cantidad; $t++) {
                Ticket::create([
                    'funcionario_id' => $funcionarios[$i]->id,
                    'tipo_problema' => $tipo,
                    'descripcion_problema' => "Ticket {$i}-{$t}",
                    'estado' => Ticket::ESTADO_PENDIENTE,
                    'fecha_creacion' => '2026-05-15',
                ]);
            }
        }

        // Tickets en otro mes para el usuario 09 que no deben alterar el ranking de mayo
        for ($t = 0; $t < 15; $t++) {
            Ticket::create([
                'funcionario_id' => $funcionarios[9]->id,
                'tipo_problema' => $tipo,
                'descripcion_problema' => "Ticket Junio {$t}",
                'estado' => Ticket::ESTADO_PENDIENTE,
                'fecha_creacion' => '2026-06-02',
            ]);
        }

        $response = $this->get(route('tickets.estadisticas', [
            'mes' => '2026-05',
            'vista' => 'usuarios',
        ]));

        $response->assertOk();
        $response->assertSee('Top 8 Usuarios que más tickets abrieron');
        $response->assertSee('Total del mes: 54');
        $response->assertSeeInOrder([
            'Usuario Ranking 01',
            'Usuario Ranking 02',
            'Usuario Ranking 08',
        ]);
        $response->assertDontSee('Usuario Ranking 09');

        $json = $this->getJson(route('tickets.estadisticas.datos', [
            'mes' => '2026-05',
            'vista' => 'usuarios',
        ]));

        $json->assertOk()
            ->assertJson([
                'vista' => 'usuarios',
                'mes' => '2026-05',
                'total_mes' => 54,
            ])
            ->assertJsonCount(8, 'labels')
            ->assertJsonCount(8, 'valores');

        $this->assertSame('Usuario Ranking 01', $json->json('labels.0'));
        $this->assertSame(10, $json->json('valores.0'));
        $this->assertSame('Usuario Ranking 08', $json->json('labels.7'));
        $this->assertSame(3, $json->json('valores.7'));
        $this->assertNotContains('Usuario Ranking 09', $json->json('labels'));
    }

    public function test_parametros_invalidos_de_mes_y_vista_usan_valores_por_defecto_sin_error(): void
    {
        $tecnico = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::TECNICO_TERCERIZADO,
            'estado_user' => 1,
        ]));

        $this->actingAs($tecnico)
            ->get(route('tickets.estadisticas', [
                'mes' => 'invalido',
                'vista' => 'cualquier_cosa',
            ]))
            ->assertOk()
            ->assertSee('Problemas más frecuentes del mes');

        $this->actingAs($tecnico)
            ->getJson(route('tickets.estadisticas.datos', [
                'mes' => 'invalido',
                'vista' => 'cualquier_cosa',
            ]))
            ->assertOk()
            ->assertJsonPath('vista', 'problemas');
    }
}
