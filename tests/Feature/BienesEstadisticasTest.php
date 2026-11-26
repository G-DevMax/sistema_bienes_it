<?php

namespace Tests\Feature;

use App\Models\Bienes\categorias;
use App\Models\Bienes\estados;
use App\Models\Bienes\items;
use App\Models\Bienes\movimientos;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\rol;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BienesEstadisticasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invitado_es_redirigido_al_login_y_usuario_sin_permiso_recibe_forbidden(): void
    {
        $this->get(route('menu_bientes'))->assertRedirect(route('login'));
        $this->get(route('bienes.estadisticas.datos'))->assertRedirect(route('login'));

        foreach ([rol::FUNCIONARIO, rol::TECNICO_TERCERIZADO] as $rolId) {
            $usuario = User::withoutEvents(fn (): User => User::factory()->create([
                'rol_id' => $rolId,
                'estado_user' => 1,
            ]));

            $this->actingAs($usuario)
                ->get(route('menu_bientes'))
                ->assertForbidden();

            $this->actingAs($usuario)
                ->get(route('bienes.estadisticas.datos'))
                ->assertForbidden();
        }
    }

    public function test_supervisor_ve_panel_circular_de_prestamos_y_transferencias_del_mes(): void
    {
        $supervisor = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::SUPERVISOR,
            'estado_user' => 1,
        ]));
        $this->actingAs($supervisor);

        $item = $this->crearItemDePrueba();
        $funcionario = $this->crearFuncionarioDePrueba('Funcionario Movimientos Mes');

        movimientos::whereBetween('fecha_movimiento', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->delete();

        $prestamoCerrado = movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => '2026-04-10 10:00:00',
            'fecha_devolucion_prevista' => '2026-04-20',
            'cantidad' => 1,
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::DEVOLUCION,
            'movimiento_origen_id' => $prestamoCerrado->id,
            'fecha_movimiento' => '2026-04-12 10:00:00',
            'cantidad' => 1,
        ]));

        for ($i = 0; $i < 2; $i++) {
            movimientos::withoutEvents(fn (): movimientos => movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $funcionario->id,
                'user_id' => $supervisor->id,
                'tipo_movimiento' => movimientos::PRESTAMO,
                'fecha_movimiento' => '2026-04-10 10:00:00',
                'fecha_devolucion_prevista' => '2026-04-20',
                'cantidad' => 1,
            ]));
        }

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::TRANSFERENCIA,
            'fecha_movimiento' => '2026-04-15 11:00:00',
            'cantidad' => 1,
        ]));

        // Movimientos que NO deben contabilizarse: ingreso, devolucion, baja, anulado, y otro mes
        foreach ([movimientos::INGRESO, movimientos::DEVOLUCION, movimientos::BAJA] as $tipoExcluido) {
            movimientos::withoutEvents(fn (): movimientos => movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $funcionario->id,
                'user_id' => $supervisor->id,
                'tipo_movimiento' => $tipoExcluido,
                'fecha_movimiento' => '2026-04-16 09:00:00',
                'cantidad' => 1,
            ]));
        }

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => '2026-04-17 09:00:00',
            'fecha_devolucion_prevista' => '2026-04-25',
            'cantidad' => 1,
            'anulado_at' => '2026-04-17 12:00:00',
            'anulado_por' => $supervisor->id,
            'motivo_anulacion' => 'Error de carga',
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::TRANSFERENCIA,
            'fecha_movimiento' => '2026-05-02 09:00:00',
            'cantidad' => 1,
        ]));

        $response = $this->get(route('menu_bientes', ['mes' => '2026-04']));

        $response->assertOk();
        $response->assertSee('Panel de Movimientos');
        $response->assertSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertSee('<canvas id="grafico-estadisticas"', false);
        $response->assertSee('data-chart-type="pie"', false);
        $response->assertSee('data-chart-data=', false);
        $response->assertDontSee('data-chart-url', false);
        $response->assertSee('"total_mes":4', false);
        $response->assertSee('estadisticas-charts', false);
        $response->assertSee('Total del mes: 4');

        $json = $this->getJson(route('bienes.estadisticas.datos', ['mes' => '2026-04']));
        $json->assertOk()
            ->assertJson([
                'vista' => 'movimientos',
                'mes' => '2026-04',
                'total_mes' => 4,
            ]);
        $this->assertSame('Préstamo', $json->json('labels.0'));
        $this->assertSame(3, $json->json('valores.0'));
        $this->assertSame('Transferencia', $json->json('labels.1'));
        $this->assertSame(1, $json->json('valores.1'));
    }

    public function test_tarjetas_muestran_prestamos_pendientes_y_devueltos_del_mes(): void
    {
        $supervisor = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::SUPERVISOR,
            'estado_user' => 1,
        ]));
        $this->actingAs($supervisor);

        $item = $this->crearItemDePrueba();
        $funcionario = $this->crearFuncionarioDePrueba('Funcionario Tarjetas Prestamos');

        movimientos::whereBetween('fecha_movimiento', ['2026-04-01 00:00:00', '2026-04-30 23:59:59'])->delete();

        $prestamoCerrado = movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => '2026-04-08 10:00:00',
            'fecha_devolucion_prevista' => '2026-04-18',
            'cantidad' => 2,
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::DEVOLUCION,
            'movimiento_origen_id' => $prestamoCerrado->id,
            'fecha_movimiento' => '2026-04-11 10:00:00',
            'cantidad' => 2,
        ]));

        $prestamoParcial = movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => '2026-04-12 10:00:00',
            'fecha_devolucion_prevista' => '2026-04-22',
            'cantidad' => 2,
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::DEVOLUCION,
            'movimiento_origen_id' => $prestamoParcial->id,
            'fecha_movimiento' => '2026-04-13 10:00:00',
            'cantidad' => 1,
        ]));

        movimientos::withoutEvents(fn (): movimientos => movimientos::create([
            'item_id' => $item->id,
            'funcionario_id' => $funcionario->id,
            'user_id' => $supervisor->id,
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => '2026-04-15 10:00:00',
            'fecha_devolucion_prevista' => '2026-04-25',
            'cantidad' => 1,
        ]));

        $response = $this->get(route('menu_bientes', ['mes' => '2026-04']));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Préstamos pendientes de devolución',
            '>2</p>',
            'Préstamos devueltos (cerrados)',
            '>1</p>',
        ], false);
    }

    public function test_filtro_usuarios_devuelve_top_8_en_vista_y_json_ordenados_de_mayor_a_menor(): void
    {
        $director = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::DIRECTOR,
            'estado_user' => 1,
        ]));
        $this->actingAs($director);

        $item = $this->crearItemDePrueba();

        movimientos::whereBetween('fecha_movimiento', ['2026-05-01 00:00:00', '2026-05-31 23:59:59'])->delete();

        $funcionarios = [];
        for ($i = 1; $i <= 9; $i++) {
            $funcionarios[$i] = $this->crearFuncionarioDePrueba(sprintf('Funcionario Mov Ranking %02d', $i));
            $cantidad = 11 - $i;

            for ($m = 0; $m < $cantidad; $m++) {
                movimientos::withoutEvents(fn (): movimientos => movimientos::create([
                    'item_id' => $item->id,
                    'funcionario_id' => $funcionarios[$i]->id,
                    'user_id' => $director->id,
                    'tipo_movimiento' => $m % 2 === 0 ? movimientos::PRESTAMO : movimientos::TRANSFERENCIA,
                    'fecha_movimiento' => '2026-05-15 10:00:00',
                    'fecha_devolucion_prevista' => $m % 2 === 0 ? '2026-05-25' : null,
                    'cantidad' => 1,
                ]));
            }
        }

        // Movimientos en otro mes para el funcionario 09 que no deben alterar el ranking de mayo
        for ($m = 0; $m < 15; $m++) {
            movimientos::withoutEvents(fn (): movimientos => movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $funcionarios[9]->id,
                'user_id' => $director->id,
                'tipo_movimiento' => movimientos::TRANSFERENCIA,
                'fecha_movimiento' => '2026-06-02 10:00:00',
                'cantidad' => 1,
            ]));
        }

        $response = $this->get(route('menu_bientes', [
            'mes' => '2026-05',
            'vista' => 'usuarios',
        ]));

        $response->assertOk();
        $response->assertSee('Usuarios con más movimientos del mes');
        $response->assertSee('Total del mes: 54');
        $response->assertSeeInOrder([
            'Funcionario Mov Ranking 01',
            'Funcionario Mov Ranking 02',
            'Funcionario Mov Ranking 08',
        ]);
        $response->assertDontSee('Funcionario Mov Ranking 09');

        $json = $this->getJson(route('bienes.estadisticas.datos', [
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

        $this->assertSame('Funcionario Mov Ranking 01', $json->json('labels.0'));
        $this->assertSame(10, $json->json('valores.0'));
        $this->assertSame('Funcionario Mov Ranking 08', $json->json('labels.7'));
        $this->assertSame(3, $json->json('valores.7'));
        $this->assertNotContains('Funcionario Mov Ranking 09', $json->json('labels'));
    }

    public function test_parametros_invalidos_de_mes_y_vista_usan_valores_por_defecto_sin_error(): void
    {
        $supervisor = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::SUPERVISOR,
            'estado_user' => 1,
        ]));

        $this->actingAs($supervisor)
            ->get(route('menu_bientes', [
                'mes' => 'invalido',
                'vista' => 'cualquier_cosa',
            ]))
            ->assertOk()
            ->assertSee('Préstamos y transferencias del mes');

        $this->actingAs($supervisor)
            ->getJson(route('bienes.estadisticas.datos', [
                'mes' => 'invalido',
                'vista' => 'cualquier_cosa',
            ]))
            ->assertOk()
            ->assertJsonPath('vista', 'movimientos');
    }

    private function crearItemDePrueba(): items
    {
        $categoria = categorias::first() ?? categorias::withoutEvents(
            fn (): categorias => categorias::create(['nombre' => 'Categoria Stats Test'])
        );

        return items::withoutEvents(fn (): items => items::create([
            'categoria_id' => $categoria->id,
            'descripcion' => 'Item Stats Movimientos Test',
            'stock_total' => 100,
            'stock_disponible' => 100,
            'estado_id' => estados::DISPONIBLE,
        ]));
    }

    private function crearFuncionarioDePrueba(string $nombre): funcionarios
    {
        $dependencia = dependencias::first() ?? dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Mov Stats Test'])
        );

        return funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => $nombre,
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));
    }
}
