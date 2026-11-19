<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\rol;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_valid_login_persists_session_in_cache_and_sets_remember_cookie(): void
    {
        config(['session.driver' => 'cache']);
        $user = User::factory()->create([
            'password' => 'secret123',
            'estado_user' => 1,
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/items');
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertInstanceOf(CacheBasedSessionHandler::class, $this->app['session']->driver()->getHandler());
        $this->assertNotEmpty(Cache::get($this->app['session']->driver()->getId()));
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
            'estado_user' => 0,
        ]);

        $response = $this->from(route('login'))->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'error' => 'Las credenciales no coinciden o su cuenta está desactivada.',
        ]);
        $this->assertGuest();
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.post'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'El correo electrónico es obligatorio.',
            'password' => 'La contraseña es obligatoria.',
        ]);
        $this->assertGuest();
    }

    public function test_deactivated_user_with_active_session_is_logged_out_and_redirected_to_login(): void
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create([
            'password' => 'secret123',
            'estado_user' => 1,
            'rol_id' => rol::DIRECTOR,
        ]));

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/items');

        $this->get(route('items.index'))->assertOk();

        User::withoutEvents(fn (): bool => $user->update(['estado_user' => 0]));
        $this->app['auth']->forgetGuards();

        $this->get(route('items.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('error');

        $this->assertGuest();
    }

    public function test_deactivating_user_or_changing_password_rotates_remember_token(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));
        $target = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::DIRECTOR,
            'estado_user' => 1,
            'remember_token' => 'token-inicial-para-prueba',
        ]));

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $target))
            ->assertRedirect(route('usuarios.index'));

        $target->refresh();
        $this->assertSame(0, $target->estado_user);
        $this->assertNotSame('token-inicial-para-prueba', $target->getRememberToken());

        $tokenPrevio = $target->getRememberToken();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'nuevaClave123',
                'rol_id' => rol::DIRECTOR,
                'estado_user' => 1,
            ])
            ->assertRedirect(route('usuarios.index'));

        $target->refresh();
        $this->assertSame(1, $target->estado_user);
        $this->assertNotSame($tokenPrevio, $target->getRememberToken());
    }

    public function test_user_with_active_session_is_logged_out_when_password_changes(): void
    {
        $user = User::withoutEvents(fn (): User => User::factory()->create([
            'password' => 'secret123',
            'estado_user' => 1,
            'rol_id' => rol::DIRECTOR,
        ]));

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/items');

        $this->get(route('items.index'))->assertOk();

        User::withoutEvents(fn (): bool => $user->update(['password' => 'claveCambiada456']));
        $this->app['auth']->forgetGuards();

        $this->get(route('items.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_changing_own_password_keeps_current_session_alive(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'password' => 'secret123',
            'estado_user' => 1,
            'rol_id' => rol::ADMIN,
            'remember_token' => 'token-admin-inicial',
        ]));

        $this->post(route('login.post'), [
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertRedirect('/items');

        $this->put(route('usuarios.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => 'nuevaClaveAdmin123',
        ])->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success');

        $admin->refresh();
        $this->assertNotSame('token-admin-inicial', $admin->getRememberToken());

        $this->app['auth']->forgetGuards();

        $this->get(route('usuarios.index'))->assertOk();
        $this->assertAuthenticatedAs($admin);
    }
}
