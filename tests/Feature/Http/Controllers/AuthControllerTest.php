<?php

namespace Tests\Feature\Http\Controllers;

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
}
