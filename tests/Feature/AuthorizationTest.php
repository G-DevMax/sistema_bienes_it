<?php

namespace Tests\Feature;

use App\Enums\TipoProblema;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\historial_cambios;
use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private function como(User $usuario): static
    {
        $this->flushSession();

        return $this->actingAs($usuario);
    }

    private function crearUsuarioConRol(int $rolId, int $estado = 1): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => $rolId,
            'estado_user' => $estado,
            'password' => 'secret123',
        ]));
    }

    private function crearTicket(?int $asignadoA = null, string $estado = Ticket::ESTADO_PENDIENTE): Ticket
    {
        $funcionario = funcionarios::where('estado_funcionario', 1)->first();

        if ($funcionario === null) {
            $dependencia = dependencias::first() ?? dependencias::withoutEvents(
                fn (): dependencias => dependencias::create(['nombre' => 'Dependencia Auth Test'])
            );

            $funcionario = funcionarios::withoutEvents(
                fn (): funcionarios => funcionarios::create([
                    'nombre' => 'Funcionario Auth Test',
                    'dependencia_id' => $dependencia->id,
                    'estado_funcionario' => 1,
                ])
            );
        }

        return Ticket::create([
            'funcionario_id' => $funcionario->id,
            'tipo_problema' => TipoProblema::values()[0],
            'descripcion_problema' => 'Ticket de prueba autorización',
            'estado' => $estado,
            'asignado_a' => $asignadoA,
            'fecha_creacion' => now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, array{int, array<string, bool>}>
     */
    public static function matrizPermisosPorRol(): array
    {
        return [
            'Administrador IT' => [
                rol::ADMIN,
                [
                    'acceso.sistema' => true,
                    'admin' => true,
                    'usuarios.gestionar' => true,
                    'historial.descargar' => true,
                    'bienes.ver' => true,
                    'bienes.crear' => true,
                    'bienes.editar' => true,
                    'bienes.eliminar' => true,
                    'funcionarios.ver' => true,
                    'funcionarios.gestionar' => true,
                    'tickets.ver' => true,
                    'tickets.crear' => true,
                    'tickets.gestionar' => true,
                    'informes.ver' => true,
                    'informes.crear' => true,
                    'informes.editar' => true,
                    'informes.eliminar' => true,
                    'detalles.ver' => true,
                    'detalles.gestionar' => true,
                ],
            ],
            'Supervisor IT' => [
                rol::SUPERVISOR,
                [
                    'acceso.sistema' => true,
                    'admin' => false,
                    'usuarios.gestionar' => false,
                    'historial.descargar' => false,
                    'bienes.ver' => true,
                    'bienes.crear' => true,
                    'bienes.editar' => false,
                    'bienes.eliminar' => false,
                    'funcionarios.ver' => true,
                    'funcionarios.gestionar' => false,
                    'tickets.ver' => true,
                    'tickets.crear' => true,
                    'tickets.gestionar' => false,
                    'informes.ver' => true,
                    'informes.crear' => true,
                    'informes.editar' => false,
                    'informes.eliminar' => false,
                    'detalles.ver' => true,
                    'detalles.gestionar' => false,
                ],
            ],
            'Director/Jefe de Área' => [
                rol::DIRECTOR,
                [
                    'acceso.sistema' => true,
                    'admin' => false,
                    'usuarios.gestionar' => false,
                    'historial.descargar' => false,
                    'bienes.ver' => true,
                    'bienes.crear' => false,
                    'bienes.editar' => false,
                    'bienes.eliminar' => false,
                    'funcionarios.ver' => true,
                    'funcionarios.gestionar' => false,
                    'tickets.ver' => false,
                    'tickets.crear' => false,
                    'tickets.gestionar' => false,
                    'informes.ver' => false,
                    'informes.crear' => false,
                    'informes.editar' => false,
                    'informes.eliminar' => false,
                    'detalles.ver' => false,
                    'detalles.gestionar' => false,
                ],
            ],
            'Funcionario' => [
                rol::FUNCIONARIO,
                [
                    'acceso.sistema' => false,
                    'admin' => false,
                    'usuarios.gestionar' => false,
                    'historial.descargar' => false,
                    'bienes.ver' => false,
                    'bienes.crear' => false,
                    'bienes.editar' => false,
                    'bienes.eliminar' => false,
                    'funcionarios.ver' => false,
                    'funcionarios.gestionar' => false,
                    'tickets.ver' => false,
                    'tickets.crear' => false,
                    'tickets.gestionar' => false,
                    'informes.ver' => false,
                    'informes.crear' => false,
                    'informes.editar' => false,
                    'informes.eliminar' => false,
                    'detalles.ver' => false,
                    'detalles.gestionar' => false,
                ],
            ],
            'Técnico Tercerizado' => [
                rol::TECNICO_TERCERIZADO,
                [
                    'acceso.sistema' => true,
                    'admin' => false,
                    'usuarios.gestionar' => false,
                    'historial.descargar' => false,
                    'bienes.ver' => false,
                    'bienes.crear' => false,
                    'bienes.editar' => false,
                    'bienes.eliminar' => false,
                    'funcionarios.ver' => false,
                    'funcionarios.gestionar' => false,
                    'tickets.ver' => true,
                    'tickets.crear' => true,
                    'tickets.gestionar' => true,
                    'informes.ver' => true,
                    'informes.crear' => true,
                    'informes.editar' => true,
                    'informes.eliminar' => false,
                    'detalles.ver' => true,
                    'detalles.gestionar' => false,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, bool>  $esperados
     */
    #[DataProvider('matrizPermisosPorRol')]
    public function test_gates_respetan_la_matriz_de_permisos_por_rol(int $rolId, array $esperados): void
    {
        $usuario = new User(['rol_id' => $rolId, 'estado_user' => 1]);
        $usuario->id = 999;

        foreach ($esperados as $habilidad => $permitido) {
            $this->assertSame(
                $permitido,
                Gate::forUser($usuario)->allows($habilidad),
                "Falló el gate '{$habilidad}' para el rol {$rolId}"
            );
        }
    }

    public function test_gate_tickets_operar_respeta_propiedad_y_rol(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);
        $tecnico1 = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $tecnico2 = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $supervisor = $this->crearUsuarioConRol(rol::SUPERVISOR);
        $director = $this->crearUsuarioConRol(rol::DIRECTOR);
        $funcionario = $this->crearUsuarioConRol(rol::FUNCIONARIO);

        $ticketPendiente = $this->crearTicket(null, Ticket::ESTADO_PENDIENTE);
        $ticketDeTecnico1 = $this->crearTicket($tecnico1->id, Ticket::ESTADO_ASIGNADO);

        $this->assertTrue(Gate::forUser($admin)->allows('tickets.operar', $ticketPendiente));
        $this->assertTrue(Gate::forUser($admin)->allows('tickets.operar', $ticketDeTecnico1));

        $this->assertTrue(Gate::forUser($tecnico1)->allows('tickets.operar', $ticketPendiente));
        $this->assertTrue(Gate::forUser($tecnico1)->allows('tickets.operar', $ticketDeTecnico1));

        $this->assertTrue(Gate::forUser($tecnico2)->allows('tickets.operar', $ticketPendiente));
        $this->assertFalse(Gate::forUser($tecnico2)->allows('tickets.operar', $ticketDeTecnico1));

        $this->assertFalse(Gate::forUser($supervisor)->allows('tickets.operar', $ticketPendiente));
        $this->assertFalse(Gate::forUser($director)->allows('tickets.operar', $ticketPendiente));
        $this->assertFalse(Gate::forUser($funcionario)->allows('tickets.operar', $ticketPendiente));
    }

    public function test_solo_admin_accede_a_usuarios_y_descarga_historial(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);
        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($admin)->get(route('usuarios.create'))->assertOk();
        $this->actingAs($admin)
            ->get(route('historial.descargar', ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-03-31']))
            ->assertOk();

        foreach ([rol::SUPERVISOR, rol::DIRECTOR, rol::FUNCIONARIO, rol::TECNICO_TERCERIZADO] as $rolId) {
            $usuario = $this->crearUsuarioConRol($rolId);

            $this->como($usuario)->get(route('usuarios.index'))->assertForbidden();
            $this->como($usuario)->post(route('usuarios.store'), [])->assertForbidden();
            $this->como($usuario)
                ->get(route('historial.descargar', ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-03-31']))
                ->assertForbidden();
        }
    }

    public function test_descarga_historial_limita_rango_a_tres_meses_y_transmite_json_valido(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);
        $this->actingAs($admin);

        historial_cambios::whereBetween('fecha_cambio', ['2026-01-01 00:00:00', '2026-06-30 23:59:59'])->delete();

        // Rango superior a 3 meses es rechazado
        $this->get(route('historial.descargar', [
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-04-02',
        ]))
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error', 'El rango máximo permitido para descargar el historial es de 3 meses.');

        // Rango vacío válido devuelve JSON []
        $respuestaVacia = $this->get(route('historial.descargar', [
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-31',
        ]))->assertOk();

        $this->assertSame([], json_decode($respuestaVacia->streamedContent(), true, 512, JSON_THROW_ON_ERROR));

        // Rango exacto de 3 meses con registros devuelve JSON válido en orden descendente
        $reg1 = historial_cambios::create([
            'user_id' => $admin->id,
            'entidad' => User::class,
            'entidad_id' => $admin->id,
            'accion' => 'Create',
            'detalle' => json_encode(['campo' => 'primero']),
            'fecha_cambio' => '2026-01-15 10:00:00',
        ]);

        $reg2 = historial_cambios::create([
            'user_id' => $admin->id,
            'entidad' => User::class,
            'entidad_id' => $admin->id,
            'accion' => 'Update',
            'detalle' => json_encode(['campo' => 'segundo']),
            'fecha_cambio' => '2026-04-01 12:30:00',
        ]);

        $respuesta = $this->get(route('historial.descargar', [
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-04-01',
        ]))->assertOk();

        $datos = json_decode($respuesta->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $datos);
        $this->assertSame($reg2->id, $datos[0]['id']);
        $this->assertSame($admin->name, $datos[0]['usuario']);
        $this->assertSame('User', $datos[0]['entidad']);
        $this->assertSame(['campo' => 'segundo'], $datos[0]['detalle']);
        $this->assertSame($reg1->id, $datos[1]['id']);
    }

    public function test_historial_cambios_omite_campos_ocultos_remember_token_y_guarda_solo_modificados(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);
        $this->actingAs($admin);

        $usuario = User::create([
            'name' => 'Nombre Original',
            'email' => 'historial_'.uniqid().'@example.com',
            'password' => '1234',
            'rol_id' => rol::SUPERVISOR,
            'estado_user' => 1,
        ]);
        $usuario->setRememberToken('token-inicial-123');
        $usuario->save();

        // Actualizar solo remember_token (login/logout con recordarme) no genera registro
        $conteoAntes = historial_cambios::where('entidad', User::class)
            ->where('entidad_id', $usuario->id)
            ->count();

        $usuario->setRememberToken('token-sesion-456');
        $usuario->timestamps = false;
        $usuario->save();
        $usuario->timestamps = true;

        $usuario->setRememberToken('token-cierre-789');
        $usuario->save();

        $this->assertSame(
            $conteoAntes,
            historial_cambios::where('entidad', User::class)->where('entidad_id', $usuario->id)->count()
        );

        // Actualizar name + password guarda únicamente campos cambiados no ocultos y nunca password/remember_token
        $usuario->update([
            'name' => 'Nombre Actualizado',
            'password' => '9999',
        ]);

        $ultimoUpdate = historial_cambios::where('entidad', User::class)
            ->where('entidad_id', $usuario->id)
            ->where('accion', 'Update')
            ->latest('id')
            ->first();

        $this->assertNotNull($ultimoUpdate);
        $detalle = json_decode((string) $ultimoUpdate->detalle, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Nombre Original', $detalle['original']['name'] ?? null);
        $this->assertSame('Nombre Actualizado', $detalle['cambiado']['name'] ?? null);
        $this->assertArrayNotHasKey('password', $detalle['original']);
        $this->assertArrayNotHasKey('password', $detalle['cambiado']);
        $this->assertArrayNotHasKey('remember_token', $detalle['original']);
        $this->assertArrayNotHasKey('remember_token', $detalle['cambiado']);
        $this->assertArrayNotHasKey('email', $detalle['original']);
        $this->assertArrayNotHasKey('email', $detalle['cambiado']);
    }

    public function test_endpoints_de_bienes_y_funcionarios_respetan_permisos_por_rol(): void
    {
        $supervisor = $this->crearUsuarioConRol(rol::SUPERVISOR);
        $director = $this->crearUsuarioConRol(rol::DIRECTOR);
        $tecnico = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $funcionario = $this->crearUsuarioConRol(rol::FUNCIONARIO);

        // Supervisor IT: ve y crea en bienes, ve funcionarios, pero no edita/elimina/baja/anula ni gestiona funcionarios
        $this->actingAs($supervisor)->get(route('items.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('items.create'))->assertOk();
        $this->actingAs($supervisor)->get(route('funcionarios.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('funcionarios.create'))->assertForbidden();
        $this->actingAs($supervisor)->post(route('movimientos.store'), ['tipo_movimiento' => 'baja'])->assertForbidden();
        $this->actingAs($supervisor)->post(route('movimientos.baja', 1), [])->assertForbidden();
        $this->actingAs($supervisor)->post(route('movimientos.anular', 1), [])->assertForbidden();

        // Director: solo lectura en bienes y funcionarios, bloqueado en creación/edición y en tickets/informes
        $this->como($director)->get(route('items.index'))->assertOk();
        $this->como($director)->get(route('movimientos.index'))->assertOk();
        $this->como($director)->get(route('funcionarios.index'))->assertOk();
        $this->como($director)->get(route('items.create'))->assertForbidden();
        $this->como($director)->post(route('items.store'), [])->assertForbidden();
        $this->como($director)->get(route('tickets.dashboard'))->assertForbidden();
        $this->como($director)->get(route('informes.index'))->assertForbidden();

        // Técnico Tercerizado: sin acceso a bienes ni funcionarios
        $this->como($tecnico)->get(route('items.index'))->assertForbidden();
        $this->como($tecnico)->get(route('movimientos.index'))->assertForbidden();
        $this->como($tecnico)->get(route('funcionarios.index'))->assertForbidden();

        // Funcionario: bloqueado en todos los módulos autenticados
        $this->como($funcionario)->get(route('items.index'))->assertForbidden();
        $this->como($funcionario)->get(route('tickets.dashboard'))->assertForbidden();
        $this->como($funcionario)->get(route('informes.index'))->assertForbidden();
    }

    public function test_endpoints_de_tickets_informes_y_detalles_respetan_permisos_por_rol(): void
    {
        $supervisor = $this->crearUsuarioConRol(rol::SUPERVISOR);
        $tecnico1 = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $tecnico2 = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $admin = $this->crearUsuarioConRol(rol::ADMIN);

        $ticketPendiente = $this->crearTicket(null, Ticket::ESTADO_PENDIENTE);
        $ticketAjeno = $this->crearTicket($tecnico1->id, Ticket::ESTADO_ASIGNADO);

        // Supervisor IT: ve y crea tickets/informes, ve detalles, pero no asigna/cierra tickets ni edita/elimina informes ni gestiona detalles
        $this->actingAs($supervisor)->get(route('tickets.dashboard'))->assertOk();
        $this->actingAs($supervisor)->get(route('tickets.create-manual'))->assertOk();
        $this->actingAs($supervisor)->post(route('tickets.asignar', $ticketPendiente), ['asignado_a' => $tecnico1->id])->assertForbidden();
        $this->actingAs($supervisor)->post(route('tickets.cerrar', $ticketAjeno), [])->assertForbidden();
        $this->actingAs($supervisor)->get(route('informes.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('informes.create'))->assertOk();
        $this->actingAs($supervisor)->get(route('informes.edit', 1))->assertForbidden();
        $this->actingAs($supervisor)->delete(route('informes.destroy', 1))->assertForbidden();
        $this->actingAs($supervisor)->get(route('detalles-problemas.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('detalles-problemas.create'))->assertForbidden();

        // Técnico Tercerizado: ve/crea/edita informes y opera tickets propios o pendientes, pero NO tickets ajenos ni elimina informes ni crea detalles
        $this->como($tecnico2)->get(route('tickets.dashboard'))->assertOk();
        $this->como($tecnico2)->get(route('tickets.create-manual'))->assertOk();
        $this->como($tecnico2)->post(route('tickets.asignar', $ticketAjeno), ['asignado_a' => $tecnico2->id])->assertForbidden();
        $this->como($tecnico2)->post(route('tickets.cerrar', $ticketAjeno), [])->assertForbidden();
        $this->como($tecnico2)->get(route('informes.index'))->assertOk();
        $this->como($tecnico2)->get(route('informes.create'))->assertOk();
        $this->como($tecnico2)->delete(route('informes.destroy', 1))->assertForbidden();
        $this->como($tecnico2)->get(route('detalles-problemas.index'))->assertOk();
        $this->como($tecnico2)->get(route('detalles-problemas.create'))->assertForbidden();

        // Técnico Tercerizado sí puede asignar un ticket pendiente a sí mismo
        $this->como($tecnico2)
            ->post(route('tickets.asignar', $ticketPendiente), ['asignado_a' => $tecnico2->id])
            ->assertRedirect(route('tickets.show', $ticketPendiente));
        $this->assertSame($tecnico2->id, $ticketPendiente->fresh()->asignado_a);

        // Admin sí puede reasignar un ticket ajeno
        $this->como($admin)
            ->post(route('tickets.asignar', $ticketAjeno), ['asignado_a' => $tecnico2->id])
            ->assertRedirect(route('tickets.show', $ticketAjeno));
        $this->assertSame($tecnico2->id, $ticketAjeno->fresh()->asignado_a);
    }

    public function test_login_bloquea_al_rol_funcionario_y_redirige_segun_rol(): void
    {
        $funcionario = $this->crearUsuarioConRol(rol::FUNCIONARIO);
        $this->from(route('login'))
            ->post(route('login.post'), ['email' => $funcionario->email, 'password' => 'secret123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('error');
        $this->assertGuest();

        $tecnico = $this->crearUsuarioConRol(rol::TECNICO_TERCERIZADO);
        $this->post(route('login.post'), ['email' => $tecnico->email, 'password' => 'secret123'])
            ->assertRedirect(route('tickets.dashboard'));
        $this->assertAuthenticatedAs($tecnico);

        auth()->logout();

        $director = $this->crearUsuarioConRol(rol::DIRECTOR);
        $this->post(route('login.post'), ['email' => $director->email, 'password' => 'secret123'])
            ->assertRedirect('/items');
        $this->assertAuthenticatedAs($director);
    }

    public function test_admin_no_puede_cambiar_su_propio_rol_ni_desactivarse_en_update(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);

        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'rol_id' => rol::DIRECTOR,
                'estado_user' => 0,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error');

        $admin->refresh();
        $this->assertSame(rol::ADMIN, $admin->rol_id);
        $this->assertSame(1, $admin->estado_user);

        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'name' => 'Admin Actualizado',
                'email' => $admin->email,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Admin Actualizado', $admin->name);
        $this->assertSame(rol::ADMIN, $admin->rol_id);
        $this->assertSame(1, $admin->estado_user);
    }

    public function test_navbar_oculta_y_muestra_enlaces_segun_rol(): void
    {
        $admin = $this->crearUsuarioConRol(rol::ADMIN);
        $this->be($admin);
        $routeItems = app('router')->getRoutes()->getByName('items.index');
        $requestItems = Request::create('/items', 'GET');
        $requestItems->setRouteResolver(fn () => $routeItems);
        app()->instance('request', $requestItems);

        $htmlAdmin = view('layouts.app')->render();
        $this->assertStringContainsString(route('items.index'), $htmlAdmin);
        $this->assertStringContainsString(route('movimientos.index'), $htmlAdmin);
        $this->assertStringContainsString(route('funcionarios.index'), $htmlAdmin);
        $this->assertStringContainsString(route('usuarios.index'), $htmlAdmin);

        $supervisor = $this->crearUsuarioConRol(rol::SUPERVISOR);
        $this->be($supervisor);
        $htmlSupervisor = view('layouts.app')->render();
        $this->assertStringContainsString(route('items.index'), $htmlSupervisor);
        $this->assertStringContainsString(route('movimientos.index'), $htmlSupervisor);
        $this->assertStringContainsString(route('funcionarios.index'), $htmlSupervisor);
        $this->assertStringNotContainsString(route('usuarios.index'), $htmlSupervisor);
    }
}
