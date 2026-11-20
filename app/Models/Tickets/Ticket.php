<?php

namespace App\Models\Tickets;

use App\Enums\TipoProblema;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use App\Models\User;
use App\Traits\BuscadorUniversal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use BuscadorUniversal, HasFactory;

    public const ANIO_MINIMO = 2026;

    public const TIPO_ASISTENCIA_REUNION = TipoProblema::AsistenciaAReunion->value;

    public const ESTADO_PENDIENTE = 'Pendiente';

    public const ESTADO_ASIGNADO = 'Asignado';

    public const ESTADO_CERRADO = 'Cerrado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_ASIGNADO,
        self::ESTADO_CERRADO,
    ];

    private const BADGES_TIPO = [
        'Equipo/Dispositivo' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        'Programa/Sistema' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        'Internet' => 'bg-[#1CD6CE]/20 text-[#1CD6CE] border-[#1CD6CE]/30',
        'Impresora/Escáner' => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
        'Cuentas y Accesos' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
        'Asistencia a Reunión' => 'bg-pink-500/20 text-pink-300 border-pink-500/30',
        'Consulta' => 'bg-green-500/20 text-green-300 border-green-500/30',
        'Insumos' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    ];

    private const BADGES_ESTADO = [
        self::ESTADO_PENDIENTE => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
        self::ESTADO_ASIGNADO => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
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

    public function informe(): HasOne
    {
        return $this->hasOne(informes::class, 'ticket_id');
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::ESTADO_CERRADO;
    }

    public function detalleTexto(): string
    {
        return $this->detalle?->detalle ?? 'Otro (especificar)';
    }

    public static function badgeTipo(string $tipo): string
    {
        return self::BADGES_TIPO[$tipo] ?? 'bg-white/10 text-white border-white/20';
    }

    public static function badgeEstado(string $estado): string
    {
        return self::BADGES_ESTADO[$estado] ?? 'bg-white/10 text-white border-white/20';
    }

    /**
     * Valida que el mes (YYYY-MM) corresponda a un mes real dentro del rango de años permitido.
     */
    public static function mesValidoEnRango(mixed $mes, int $anioMinimo = self::ANIO_MINIMO, ?int $anioMaximo = null): ?string
    {
        if (! is_string($mes) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes, $coincidencias)) {
            return null;
        }

        $anio = (int) $coincidencias[1];
        $limiteSuperior = $anioMaximo ?? max(now()->addDay()->year, $anioMinimo);

        return ($anio >= $anioMinimo && $anio <= $limiteSuperior) ? $mes : null;
    }

    /**
     * Calcula el año mínimo (>= 2026) y máximo usando el año actual y los registros existentes en BD.
     *
     * @return array{min: int, max: int}
     */
    public static function rangoAniosDashboard(): array
    {
        $rango = self::query()
            ->selectRaw('MIN(fecha_creacion) as min_creacion, MAX(fecha_creacion) as max_creacion, MAX(fecha_cierre) as max_cierre')
            ->first();

        $anioActual = max(now()->year, self::ANIO_MINIMO);
        $anioMinDb = (int) substr((string) ($rango->min_creacion ?? ''), 0, 4);
        $anioMaxDb = max(
            (int) substr((string) ($rango->max_creacion ?? ''), 0, 4),
            (int) substr((string) ($rango->max_cierre ?? ''), 0, 4)
        );

        $min = $anioMinDb >= self::ANIO_MINIMO ? min($anioMinDb, $anioActual) : self::ANIO_MINIMO;
        $max = max($anioActual, $anioMaxDb, $min);

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Construye la descripción final del ticket, anexando fecha, horario y detalles si es asistencia a reunión.
     */
    public static function formatearDescripcion(
        string $tipoProblema,
        ?string $descripcion,
        ?string $fechaReunion = null,
        ?string $horarioReunion = null,
        ?string $detallesReunion = null
    ): string {
        if ($tipoProblema !== self::TIPO_ASISTENCIA_REUNION) {
            return trim((string) $descripcion);
        }

        $fechaFormateada = Carbon::parse((string) $fechaReunion)->format('d/m/Y');

        return self::TIPO_ASISTENCIA_REUNION
            ."\nFecha: ".$fechaFormateada
            ."\nHorario aproximado: ".trim((string) $horarioReunion)
            ."\nDetalles de la reunión: ".trim((string) $detallesReunion);
    }

    /**
     * Prepara los atributos para generar automáticamente el informe técnico al cerrar el ticket.
     *
     * @return array{ticket_id: int|null, user_id: int|null, titulo: string, funcionario_id: int, tipo_problema: string, detalle_id: int|null, observacion: string|null}
     */
    public function datosParaInforme(?int $detalleId = null, ?string $observacion = null, ?int $userId = null): array
    {
        return [
            'ticket_id' => $this->id !== null ? (int) $this->id : null,
            'user_id' => $this->asignado_a !== null ? (int) $this->asignado_a : $userId,
            'titulo' => Str::limit(trim((string) $this->descripcion_problema), 495, ''),
            'funcionario_id' => (int) $this->funcionario_id,
            'tipo_problema' => (string) $this->tipo_problema,
            'detalle_id' => $detalleId,
            'observacion' => $observacion,
        ];
    }
}
