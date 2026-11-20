<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Tickets\Ticket;
use App\Traits\LogsHistory;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsHistory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'rol_id',
        'password',
        'estado_user',
    ];

    // relacion muchos a uno con la tabla roles
    public function rol()
    {
        return $this->belongsTo(rol::class);
    }

    public function ticketsAsignados(): HasMany
    {
        return $this->hasMany(Ticket::class, 'asignado_a')
            ->where('estado', Ticket::ESTADO_ASIGNADO);
    }

    public function esAdmin(): bool
    {
        return (int) $this->rol_id === rol::ADMIN;
    }

    public function tieneRol(int ...$roles): bool
    {
        return in_array((int) $this->rol_id, $roles, true);
    }

    public function puedeIniciarSesion(): bool
    {
        return (int) $this->estado_user === 1 && ! $this->tieneRol(rol::FUNCIONARIO);
    }

    public function rutaInicio(): string
    {
        return match ((int) $this->rol_id) {
            rol::TECNICO_TERCERIZADO => route('tickets.dashboard'),
            default => '/items',
        };
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol_id' => 'integer',
            'estado_user' => 'integer',
        ];
    }
}
