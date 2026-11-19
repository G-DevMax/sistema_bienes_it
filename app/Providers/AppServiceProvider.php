<?php

namespace App\Providers;

use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\User;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Session::extend('cache', function ($app): CacheBasedSessionHandler {
            return new CacheBasedSessionHandler(
                $app->make('cache')->store(config('session.store')),
                (int) config('session.lifetime')
            );
        });

        $this->registrarPermisos();
    }

    private function registrarPermisos(): void
    {
        Gate::before(static function (User $user): ?bool {
            if ($user->esAdmin()) {
                return true;
            }

            if ($user->tieneRol(rol::FUNCIONARIO)) {
                return false;
            }

            return null;
        });

        $permisos = [
            'acceso.sistema' => [rol::SUPERVISOR, rol::DIRECTOR, rol::TECNICO_TERCERIZADO],
            'admin' => [],
            'usuarios.gestionar' => [],
            'historial.descargar' => [],

            'bienes.ver' => [rol::SUPERVISOR, rol::DIRECTOR],
            'bienes.crear' => [rol::SUPERVISOR],
            'bienes.editar' => [],
            'bienes.eliminar' => [],

            'funcionarios.ver' => [rol::SUPERVISOR, rol::DIRECTOR],
            'funcionarios.gestionar' => [],

            'tickets.ver' => [rol::SUPERVISOR, rol::TECNICO_TERCERIZADO],
            'tickets.crear' => [rol::SUPERVISOR, rol::TECNICO_TERCERIZADO],
            'tickets.gestionar' => [rol::TECNICO_TERCERIZADO],

            'informes.ver' => [rol::SUPERVISOR, rol::TECNICO_TERCERIZADO],
            'informes.crear' => [rol::SUPERVISOR, rol::TECNICO_TERCERIZADO],
            'informes.editar' => [rol::TECNICO_TERCERIZADO],
            'informes.eliminar' => [],

            'detalles.ver' => [rol::SUPERVISOR, rol::TECNICO_TERCERIZADO],
            'detalles.gestionar' => [],
        ];

        foreach ($permisos as $habilidad => $rolesPermitidos) {
            Gate::define(
                $habilidad,
                static fn (User $user): bool => $user->tieneRol(...$rolesPermitidos)
            );
        }

        Gate::define('tickets.operar', static function (User $user, Ticket $ticket): bool {
            if (! $user->tieneRol(rol::TECNICO_TERCERIZADO)) {
                return false;
            }

            return $ticket->asignado_a === null || (int) $ticket->asignado_a === (int) $user->id;
        });
    }
}
