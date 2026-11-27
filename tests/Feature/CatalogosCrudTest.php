<?php

namespace Tests\Feature;

use App\Models\Bienes\categorias;
use App\Models\Bienes\estados;
use App\Models\Bienes\items;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\rol;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CatalogosCrudTest extends TestCase
{
    use DatabaseTransactions;

    private function crearAdmin(): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
    }

    public function test_rutas_show_eliminadas_no_existen_en_catalogos_sin_vista_detalle(): void
    {
        $admin = $this->crearAdmin();

        foreach (['dependencias.show', 'categorias.show', 'funcionarios.show', 'usuarios.show', 'items.show'] as $nombreRuta) {
            $this->assertFalse(Route::has($nombreRuta), "La ruta {$nombreRuta} no debería existir.");
        }

        $this->actingAs($admin)->get('/dependencias/1')->assertMethodNotAllowed();
        $this->actingAs($admin)->get('/categorias/1')->assertMethodNotAllowed();
        $this->actingAs($admin)->get('/funcionarios/1')->assertMethodNotAllowed();
        $this->actingAs($admin)->get('/usuarios/1')->assertMethodNotAllowed();
        $this->actingAs($admin)->get('/items/1')->assertMethodNotAllowed();
    }

    public function test_dependencias_crud_completo_valida_duplicados_y_bloquea_eliminacion_con_funcionarios(): void
    {
        $admin = $this->crearAdmin();
        $sufijo = (string) random_int(100000, 999999);
        $nombreEsperado = 'Dependencia Qa '.$sufijo;
        $nombreEditadoEsperado = 'Dependencia Qa Editada '.$sufijo;

        $this->actingAs($admin)->get(route('dependencias.index'))->assertOk();
        $this->actingAs($admin)->get(route('dependencias.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('dependencias.store'), ['nombre' => 'dependencia qa '.$sufijo])
            ->assertRedirect(route('dependencias.index'))
            ->assertSessionHas('success', 'Dependencia creada exitosamente.');

        $dependencia = dependencias::where('nombre', $nombreEsperado)->first();
        $this->assertNotNull($dependencia);

        // Nombre duplicado rechazado con mensaje esperado
        $this->actingAs($admin)
            ->post(route('dependencias.store'), ['nombre' => $dependencia->nombre])
            ->assertRedirect(route('dependencias.index'))
            ->assertSessionHas('error', 'Ya existe una dependencia con este nombre.');

        $this->actingAs($admin)
            ->get(route('dependencias.edit', $dependencia))
            ->assertOk()
            ->assertSee($dependencia->nombre);

        $this->actingAs($admin)
            ->put(route('dependencias.update', $dependencia), ['nombre' => 'dependencia qa editada '.$sufijo])
            ->assertRedirect(route('dependencias.index'))
            ->assertSessionHas('success', 'Dependencia actualizada exitosamente.');

        $dependencia->refresh();
        $this->assertSame($nombreEditadoEsperado, $dependencia->nombre);

        // Bloquea eliminación cuando tiene funcionarios asignados
        $funcionario = funcionarios::withoutEvents(fn (): funcionarios => funcionarios::create([
            'nombre' => 'Funcionario Dep Qa',
            'dependencia_id' => $dependencia->id,
            'estado_funcionario' => 1,
        ]));

        $this->actingAs($admin)
            ->delete(route('dependencias.destroy', $dependencia))
            ->assertRedirect(route('dependencias.index'))
            ->assertSessionHas('error', 'No se puede eliminar la dependencia porque tiene funcionarios asignados.');
        $this->assertModelExists($dependencia);

        // Sin funcionarios se elimina correctamente
        funcionarios::withoutEvents(fn () => $funcionario->delete());

        $this->actingAs($admin)
            ->delete(route('dependencias.destroy', $dependencia))
            ->assertRedirect(route('dependencias.index'))
            ->assertSessionHas('success', 'Dependencia eliminada exitosamente.');
        $this->assertModelMissing($dependencia);

        $this->actingAs($admin)->get(route('dependencias.edit', 99999999))->assertNotFound();
    }

    public function test_categorias_crud_completo_valida_duplicados_y_bloquea_eliminacion_con_items(): void
    {
        $admin = $this->crearAdmin();
        $sufijo = (string) random_int(100000, 999999);
        $nombreEsperado = 'Categoria Qa '.$sufijo;
        $nombreEditadoEsperado = 'Categoria Qa Editada '.$sufijo;

        $this->actingAs($admin)->get(route('categorias.index'))->assertOk();
        $this->actingAs($admin)->get(route('categorias.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('categorias.store'), ['nombre' => 'categoria qa '.$sufijo])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success', 'Categoría creada exitosamente.');

        $categoria = categorias::where('nombre', $nombreEsperado)->first();
        $this->assertNotNull($categoria);

        $this->actingAs($admin)
            ->post(route('categorias.store'), ['nombre' => $categoria->nombre])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error', 'Ya existe una categoría con este nombre.');

        $this->actingAs($admin)
            ->get(route('categorias.edit', $categoria))
            ->assertOk()
            ->assertSee($categoria->nombre);

        $this->actingAs($admin)
            ->put(route('categorias.update', $categoria), ['nombre' => 'categoria qa editada '.$sufijo])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success', 'Categoría actualizada exitosamente.');

        $categoria->refresh();
        $this->assertSame($nombreEditadoEsperado, $categoria->nombre);

        $item = items::withoutEvents(fn (): items => items::create([
            'categoria_id' => $categoria->id,
            'descripcion' => 'Item Cat Qa',
            'stock_total' => 2,
            'stock_disponible' => 2,
            'estado_id' => estados::NO_APLICA,
        ]));

        $this->actingAs($admin)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error', 'No se puede eliminar la categoría porque tiene items asociados.');
        $this->assertModelExists($categoria);

        items::withoutEvents(fn () => $item->delete());

        $this->actingAs($admin)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success', 'Categoría eliminada exitosamente.');
        $this->assertModelMissing($categoria);

        $this->actingAs($admin)->get(route('categorias.edit', 99999999))->assertNotFound();
    }

    public function test_funcionarios_crear_editar_actualizar_y_validar_campos(): void
    {
        $admin = $this->crearAdmin();
        $dependencia = dependencias::withoutEvents(
            fn (): dependencias => dependencias::create(['nombre' => 'Dep Func Qa '.uniqid()])
        );
        $nombre = 'Juan Perez Qa '.uniqid();
        $nombreEditado = 'Juan Perez Editado '.uniqid();

        $this->actingAs($admin)->get(route('funcionarios.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('funcionarios.store'), [
                'nombre' => '',
                'dependencia_id' => 99999999,
            ])
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('error', 'El nombre del funcionario es obligatorio.');

        $this->actingAs($admin)
            ->post(route('funcionarios.store'), [
                'nombre' => strtolower($nombre),
                'dependencia_id' => $dependencia->id,
            ])
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('success', 'Funcionario registrado exitosamente.');

        $funcionario = funcionarios::where('dependencia_id', $dependencia->id)->latest('id')->first();
        $this->assertNotNull($funcionario);
        $this->assertSame(1, (int) $funcionario->estado_funcionario);

        $this->actingAs($admin)
            ->get(route('funcionarios.edit', $funcionario))
            ->assertOk()
            ->assertSee($funcionario->nombre);

        $this->actingAs($admin)
            ->put(route('funcionarios.update', $funcionario), [
                'nombre' => $nombreEditado,
                'dependencia_id' => $dependencia->id,
                'estado_funcionario' => '1',
            ])
            ->assertRedirect(route('funcionarios.index'))
            ->assertSessionHas('success', 'Funcionario actualizado exitosamente.');

        $this->assertSame($nombreEditado, $funcionario->fresh()->nombre);
    }

    public function test_usuarios_crear_editar_y_validar_correo_duplicado(): void
    {
        $admin = $this->crearAdmin();
        $correo = 'nuevo_usuario_'.uniqid().'@example.com';

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'maria lopez qa',
                'email' => strtoupper($correo),
                'password' => 'clave1234',
                'rol_id' => rol::DIRECTOR,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'Usuario creado exitosamente.');

        $usuario = User::where('email', $correo)->first();
        $this->assertNotNull($usuario);
        $this->assertSame('Maria Lopez Qa', $usuario->name);
        $this->assertSame(rol::DIRECTOR, $usuario->rol_id);
        $this->assertSame(1, $usuario->estado_user);

        // Correo duplicado rechazado
        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Otro Usuario',
                'email' => $correo,
                'password' => 'clave1234',
                'rol_id' => rol::DIRECTOR,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error', 'Este correo electrónico ya se encuentra registrado.');

        $this->actingAs($admin)
            ->get(route('usuarios.edit', $usuario))
            ->assertOk()
            ->assertSee($usuario->name);

        $this->actingAs($admin)->get(route('usuarios.edit', 99999999))->assertNotFound();
    }
}
