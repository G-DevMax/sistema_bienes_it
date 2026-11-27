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

class BienesOperacionesTest extends TestCase
{
    use DatabaseTransactions;

    private function crearAdmin(): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
    }

    private function crearCategoria(): categorias
    {
        return categorias::withoutEvents(
            fn (): categorias => categorias::create(['nombre' => 'Categoria Op Qa '.uniqid()])
        );
    }

    private function crearFuncionario(): funcionarios
    {
        $dependencia = dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dep Op Qa '.uniqid()])
        );

        return funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Op Qa '.uniqid(),
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));
    }

    public function test_items_crear_generico_y_serializado_editar_actualizar_ingresar_stock_y_redirigir_baja(): void
    {
        $admin = $this->crearAdmin();
        $categoria = $this->crearCategoria();
        $descGenerico = 'Cable HDMI Qa '.uniqid();
        $codigoSerial = 'PAT-QA-'.uniqid();

        // Crear item genérico
        $this->actingAs($admin)
            ->post(route('items.store'), [
                'descripcion' => $descGenerico,
                'categoria_id' => $categoria->id,
                'tiene_codigo' => '0',
                'stock' => 5,
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success', 'Item creado correctamente.');

        $itemGenerico = items::where('descripcion', $descGenerico)->first();
        $this->assertNotNull($itemGenerico);
        $this->assertNull($itemGenerico->codigo_patrimonial);
        $this->assertSame(5, $itemGenerico->stock_total);
        $this->assertSame(5, $itemGenerico->stock_disponible);

        // Ingresar stock adicional en item genérico
        $this->actingAs($admin)
            ->post(route('items.ingresar-stock', $itemGenerico), [
                'cantidad' => 3,
                'observaciones' => 'Compra trimestral QA',
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success', 'Se ingresaron 3 unidad(es) al stock');

        $itemGenerico->refresh();
        $this->assertSame(8, $itemGenerico->stock_total);
        $this->assertSame(8, $itemGenerico->stock_disponible);

        // Crear item serializado
        $this->actingAs($admin)
            ->post(route('items.store'), [
                'descripcion' => 'Notebook QA',
                'categoria_id' => $categoria->id,
                'tiene_codigo' => '1',
                'codigo_patrimonial' => $codigoSerial,
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success', 'Item creado correctamente.');

        $itemSerializado = items::where('codigo_patrimonial', $codigoSerial)->first();
        $this->assertNotNull($itemSerializado);
        $this->assertSame(1, $itemSerializado->stock_total);
        $this->assertSame(estados::DISPONIBLE, $itemSerializado->estado_id);

        // Editar y actualizar item serializado
        $this->actingAs($admin)
            ->get(route('items.edit', $itemSerializado))
            ->assertOk()
            ->assertSee($codigoSerial);

        $nuevoCodigo = 'PAT-QA-UPD-'.uniqid();
        $this->actingAs($admin)
            ->put(route('items.update', $itemSerializado), [
                'descripcion' => 'Notebook QA Actualizada',
                'categoria_id' => $categoria->id,
                'codigo_patrimonial' => $nuevoCodigo,
            ])
            ->assertRedirect(route('items.index'))
            ->assertSessionHas('success', 'Item actualizado correctamente.');

        $itemSerializado->refresh();
        $this->assertSame('Notebook QA Actualizada', $itemSerializado->descripcion);
        $this->assertSame($nuevoCodigo, $itemSerializado->codigo_patrimonial);

        // DELETE redirige al formulario de baja de movimientos
        $this->actingAs($admin)
            ->delete(route('items.destroy', $itemSerializado))
            ->assertRedirect(route('movimientos.create', ['tipo' => 'baja', 'item_id' => $itemSerializado->id]));
    }

    public function test_movimientos_prestamo_filtro_pendientes_devolucion_baja_anulacion_planilla_e_informe(): void
    {
        $admin = $this->crearAdmin();
        $categoria = $this->crearCategoria();
        $funcionario = $this->crearFuncionario();

        $item = items::withoutEvents(fn (): items => items::create([
            'categoria_id' => $categoria->id,
            'descripcion' => 'Mouse Optico Qa '.uniqid(),
            'stock_total' => 10,
            'stock_disponible' => 10,
            'estado_id' => estados::NO_APLICA,
        ]));

        $this->actingAs($admin)->get(route('movimientos.create'))->assertOk();

        // Registrar préstamo de 4 unidades
        $responseStore = $this->actingAs($admin)
            ->post(route('movimientos.store'), [
                'tipo_movimiento' => movimientos::PRESTAMO,
                'item_id' => [$item->id],
                'cantidad' => [4],
                'funcionario_id' => $funcionario->id,
                'fecha_movimiento' => '2026-04-10',
                'fecha_devolucion_prevista' => '2026-04-20',
                'observaciones' => 'Préstamo de prueba QA',
            ]);

        $prestamo = movimientos::where('item_id', $item->id)
            ->where('tipo_movimiento', movimientos::PRESTAMO)
            ->latest('id')
            ->first();
        $this->assertNotNull($prestamo);

        $responseStore
            ->assertRedirect(route('movimientos.show', $prestamo->id))
            ->assertSessionHas('success', 'Movimiento(s) registrado(s) correctamente.');

        $this->assertSame(6, $item->fresh()->stock_disponible);

        // Ver detalle por Route Model Binding y listado con pendientes=1
        $this->actingAs($admin)
            ->get(route('movimientos.show', $prestamo))
            ->assertOk()
            ->assertSee($item->descripcion);

        $this->actingAs($admin)
            ->get(route('movimientos.index', ['pendientes' => 1, 'search' => $item->descripcion]))
            ->assertOk()
            ->assertSee($item->descripcion);

        // Descargar planilla Excel del préstamo activo
        $planilla = $this->actingAs($admin)
            ->get(route('movimientos.planilla', $prestamo))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($planilla->streamedContent());

        // Validación en devolver (fuera del try/catch) devuelve errores en bolsa $errors
        $this->actingAs($admin)
            ->from(route('movimientos.show', $prestamo))
            ->post(route('movimientos.devolver', $prestamo->id), [
                'cantidad' => 0,
                'fecha_movimiento' => '',
            ])
            ->assertRedirect(route('movimientos.show', $prestamo))
            ->assertSessionHasErrors(['cantidad', 'fecha_movimiento']);

        // Devolución parcial de 2 unidades
        $this->actingAs($admin)
            ->post(route('movimientos.devolver', $prestamo->id), [
                'cantidad' => 2,
                'fecha_movimiento' => '2026-04-12',
                'observaciones' => 'Devolución parcial QA',
            ])
            ->assertRedirect(route('movimientos.show', $prestamo->id))
            ->assertSessionHas('success', 'Devolución registrada; el stock fue actualizado.');

        $this->assertSame(8, $item->fresh()->stock_disponible);

        // Baja desde entrega con validación fallida (observaciones < 10 chars)
        $this->actingAs($admin)
            ->from(route('movimientos.show', $prestamo))
            ->post(route('movimientos.baja', $prestamo->id), [
                'cantidad' => 1,
                'fecha_movimiento' => '2026-04-13',
                'motivo' => 'Daño físico',
                'observaciones' => 'Corto',
            ])
            ->assertRedirect(route('movimientos.show', $prestamo))
            ->assertSessionHasErrors('observaciones');

        // Baja desde entrega válida de 1 unidad
        $this->actingAs($admin)
            ->post(route('movimientos.baja', $prestamo->id), [
                'cantidad' => 1,
                'fecha_movimiento' => '2026-04-13',
                'motivo' => 'Rotura accidental',
                'observaciones' => 'El funcionario informó rotura del dispositivo en oficina.',
            ])
            ->assertRedirect(route('movimientos.show', $prestamo->id))
            ->assertSessionHas('success', 'Baja registrada con nota de responsabilidad.');

        $this->assertSame(9, $item->fresh()->stock_total);
        $this->assertSame(8, $item->fresh()->stock_disponible);

        // Generar informe Excel de préstamos
        $informeXlsx = $this->actingAs($admin)
            ->get(route('movimientos.informe', [
                'fecha_desde' => '2026-04-01',
                'fecha_hasta' => '2026-04-30',
                'tipo_movimiento' => 'Prestamo',
                'funcionario' => $funcionario->id,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($informeXlsx->streamedContent());

        // Registrar segundo préstamo para probar anulación y bloqueo de planilla anulada
        $this->actingAs($admin)
            ->post(route('movimientos.store'), [
                'tipo_movimiento' => movimientos::PRESTAMO,
                'item_id' => [$item->id],
                'cantidad' => [1],
                'funcionario_id' => $funcionario->id,
                'fecha_movimiento' => '2026-04-14',
                'fecha_devolucion_prevista' => '2026-04-25',
            ])
            ->assertSessionHas('success');

        $prestamoAnulable = movimientos::where('item_id', $item->id)
            ->where('tipo_movimiento', movimientos::PRESTAMO)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(7, $item->fresh()->stock_disponible);

        $this->actingAs($admin)
            ->from(route('movimientos.show', $prestamoAnulable))
            ->post(route('movimientos.anular', $prestamoAnulable->id), ['motivo_anulacion' => ''])
            ->assertSessionHasErrors('motivo_anulacion');

        $this->actingAs($admin)
            ->post(route('movimientos.anular', $prestamoAnulable->id), [
                'motivo_anulacion' => 'Cargado por error QA',
            ])
            ->assertRedirect(route('movimientos.show', $prestamoAnulable->id))
            ->assertSessionHas('success', 'Movimiento anulado y stock revertido.');

        $this->assertSame(8, $item->fresh()->stock_disponible);
        $this->assertTrue($prestamoAnulable->fresh()->estaAnulado());

        // Planilla bloqueada en movimiento anulado
        $this->actingAs($admin)
            ->get(route('movimientos.planilla', $prestamoAnulable))
            ->assertRedirect(route('movimientos.show', $prestamoAnulable))
            ->assertSessionHas('error', 'No se puede imprimir la planilla de un movimiento anulado.');
    }
}
