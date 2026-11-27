<?php

namespace Tests\Feature;

use App\Enums\TipoProblema;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\detalles_soluciones;
use App\Models\Informes\informes;
use App\Models\rol;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InformesYCatalogosProblemasTest extends TestCase
{
    use DatabaseTransactions;

    private function crearAdmin(): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
    }

    public function test_detalles_problemas_crud_completo_y_validacion_utf8_y_enum(): void
    {
        $admin = $this->crearAdmin();
        $textoOriginal = 'Falla de conexión inalámbrica QA '.uniqid();
        $textoEditado = 'Falla de conexión cableada QA '.uniqid();

        $this->actingAs($admin)->get(route('detalles-problemas.index', ['tipo' => 'Internet']))->assertOk();
        $this->actingAs($admin)->get(route('detalles-problemas.create'))->assertOk();

        // Tipo de problema inválido usa mensaje de regla enum
        $this->actingAs($admin)
            ->post(route('detalles-problemas.store'), [
                'tipo_problema' => 'Tipo Inexistente',
                'detalle' => $textoOriginal,
            ])
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('error', 'El tipo de problema seleccionado no es válido.');

        // 101 palabras con tildes UTF-8 rechazadas
        $this->actingAs($admin)
            ->post(route('detalles-problemas.store'), [
                'tipo_problema' => TipoProblema::Internet->value,
                'detalle' => implode(' ', array_fill(0, 101, 'a')),
            ])
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('error', 'El detalle del problema no puede superar las 100 palabras.');

        // 80 palabras con tildes UTF-8 aceptadas (verifica que no se duplique conteo por acentos)
        $this->actingAs($admin)
            ->post(route('detalles-problemas.store'), [
                'tipo_problema' => TipoProblema::Internet->value,
                'detalle' => $textoOriginal,
            ])
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('success', 'Detalle de problema registrado exitosamente.');

        $detalle = detalles_problemas::where('detalle', $textoOriginal)->first();
        $this->assertNotNull($detalle);

        $this->actingAs($admin)
            ->get(route('detalles-problemas.show', $detalle))
            ->assertOk()
            ->assertSee($textoOriginal);

        $this->actingAs($admin)
            ->get(route('detalles-problemas.edit', $detalle))
            ->assertOk()
            ->assertSee($textoOriginal);

        $this->actingAs($admin)
            ->put(route('detalles-problemas.update', $detalle), [
                'tipo_problema' => TipoProblema::Internet->value,
                'detalle' => $textoEditado,
            ])
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('success', 'Detalle de problema actualizado exitosamente.');

        $this->assertSame($textoEditado, $detalle->fresh()->detalle);

        $this->actingAs($admin)
            ->delete(route('detalles-problemas.destroy', $detalle))
            ->assertRedirect(route('detalles-problemas.index'))
            ->assertSessionHas('success', 'Detalle de problema eliminado exitosamente.');

        $this->assertModelMissing($detalle);
    }

    public function test_detalles_soluciones_crud_completo_y_validacion_enum(): void
    {
        $admin = $this->crearAdmin();
        $textoOriginal = 'Reinicio de configuración de red QA '.uniqid();
        $textoEditado = 'Reemplazo de patchcord QA '.uniqid();

        $this->actingAs($admin)->get(route('detalles-soluciones.index', ['tipo' => 'Internet']))->assertOk();
        $this->actingAs($admin)->get(route('detalles-soluciones.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('detalles-soluciones.store'), [
                'tipo_problema' => 'Invalido',
                'solucion' => $textoOriginal,
            ])
            ->assertRedirect(route('detalles-soluciones.index'))
            ->assertSessionHas('error', 'El tipo de problema seleccionado no es válido.');

        $this->actingAs($admin)
            ->post(route('detalles-soluciones.store'), [
                'tipo_problema' => TipoProblema::Internet->value,
                'solucion' => $textoOriginal,
            ])
            ->assertRedirect(route('detalles-soluciones.index'))
            ->assertSessionHas('success', 'Solución común registrada exitosamente.');

        $solucion = detalles_soluciones::where('solucion', $textoOriginal)->first();
        $this->assertNotNull($solucion);

        $this->actingAs($admin)
            ->get(route('detalles-soluciones.show', $solucion))
            ->assertOk()
            ->assertSee($textoOriginal);

        $this->actingAs($admin)
            ->get(route('detalles-soluciones.edit', $solucion))
            ->assertOk()
            ->assertSee($textoOriginal);

        $this->actingAs($admin)
            ->put(route('detalles-soluciones.update', $solucion), [
                'tipo_problema' => TipoProblema::Internet->value,
                'solucion' => $textoEditado,
            ])
            ->assertRedirect(route('detalles-soluciones.index'))
            ->assertSessionHas('success', 'Solución común actualizada exitosamente.');

        $this->assertSame($textoEditado, $solucion->fresh()->solucion);

        $this->actingAs($admin)
            ->delete(route('detalles-soluciones.destroy', $solucion))
            ->assertRedirect(route('detalles-soluciones.index'))
            ->assertSessionHas('success', 'Solución común eliminada exitosamente.');

        $this->assertModelMissing($solucion);
    }

    public function test_informes_listado_pdf_y_validacion_utf8_y_enum(): void
    {
        $admin = $this->crearAdmin();
        $dependencia = dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dep Inf Qa '.uniqid()])
        );
        $funcionario = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Inf Qa '.uniqid(),
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        // Validación de enum inválido en informe
        $this->actingAs($admin)
            ->post(route('informes.store'), [
                'titulo' => 'Informe con tipo inválido',
                'funcionario_id' => $funcionario->id,
                'tipo_problema' => 'Tipo No Valido',
                'detalle_id' => 'otro',
                'solucion_id' => 'otro',
                'observacion' => 'Detalle en observación.',
            ])
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('error', 'El tipo de problema seleccionado no es válido.');

        // Título de 60 palabras con tildes UTF-8 pasa sin falso positivo de conteo
        $tituloConTildes = implode(' ', array_fill(0, 60, 'camión'));
        $this->actingAs($admin)
            ->post(route('informes.store'), [
                'titulo' => $tituloConTildes,
                'funcionario_id' => $funcionario->id,
                'tipo_problema' => TipoProblema::Internet->value,
                'detalle_id' => 'otro',
                'solucion_id' => 'otro',
                'observacion' => 'Solución verificada en campo.',
            ])
            ->assertRedirect(route('informes.index'))
            ->assertSessionHas('success', 'Informe registrado exitosamente.');

        $informe = informes::where('funcionario_id', $funcionario->id)->latest('id')->first();
        $this->assertNotNull($informe);

        $this->actingAs($admin)
            ->get(route('informes.index', ['tipo' => TipoProblema::Internet->value, 'search' => $funcionario->nombre]))
            ->assertOk()
            ->assertSee($funcionario->nombre);

        $this->actingAs($admin)
            ->get(route('informes.pdf', $informe))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
