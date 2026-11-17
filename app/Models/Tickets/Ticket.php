<?php

namespace App\Models\Tickets;

use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\User;
use App\Traits\BuscadorUniversal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use BuscadorUniversal, HasFactory;

    public const ESTADO_PENDIENTE = 'Pendiente';

    public const ESTADO_ASIGNADO = 'Asignado';

    public const ESTADO_RESUELTO = 'Resuelto';

    public const ESTADO_CERRADO = 'Cerrado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_ASIGNADO,
        self::ESTADO_RESUELTO,
        self::ESTADO_CERRADO,
    ];

    public const TIPOS_PROBLEMA = [
        'Equipo/Dispositivo',
        'Programa/Sistema',
        'Internet',
        'Impresora/Escáner',
        'Cuentas y Accesos',
        'Consulta',
        'Insumos',
    ];

    private const BADGES_TIPO = [
        'Equipo/Dispositivo' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        'Programa/Sistema' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        'Internet' => 'bg-[#1CD6CE]/20 text-[#1CD6CE] border-[#1CD6CE]/30',
        'Impresora/Escáner' => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
        'Cuentas y Accesos' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
        'Consulta' => 'bg-green-500/20 text-green-300 border-green-500/30',
        'Insumos' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    ];

    private const BADGES_ESTADO = [
        self::ESTADO_PENDIENTE => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
        self::ESTADO_ASIGNADO => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        self::ESTADO_RESUELTO => 'bg-[#1CD6CE]/20 text-[#1CD6CE] border-[#1CD6CE]/30',
        self::ESTADO_CERRADO => 'bg-green-500/20 text-green-300 border-green-500/30',
    ];

    protected $table = 'tickets';

    protected array $buscador = [
        'funcionario.nombre',
    ];

    protected $fillable = [
        'funcionario_id',
        'tipo_problema',
        'detalle_id',
        'descripcion_problema',
        'estado',
        'asignado_a',
        'fecha_creacion',
        'fecha_cierre',
        'observacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'date',
            'fecha_cierre' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(funcionarios::class, 'funcionario_id');
    }

    public function tecnicoAsignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(detalles_problemas::class, 'detalle_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::ESTADO_CERRADO;
    }

    public function detalleTexto(): string
    {
        return $this->detalle?->detalle ?? 'Otro (especificar)';
    }

    public static function tipoParaDetalle(string $tipo): string
    {
        return $tipo === 'Cuenta y Acceso' ? 'Cuentas y Accesos' : $tipo;
    }

    public static function badgeTipo(string $tipo): string
    {
        return self::BADGES_TIPO[$tipo] ?? 'bg-white/10 text-white border-white/20';
    }

    public static function badgeEstado(string $estado): string
    {
        return self::BADGES_ESTADO[$estado] ?? 'bg-white/10 text-white border-white/20';
    }
}
