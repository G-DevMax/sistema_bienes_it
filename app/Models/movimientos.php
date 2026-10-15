<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class movimientos extends Model
{
    use HasFactory, \App\Traits\LogsHistory, \App\Traits\BuscadorUniversal;

    // tipos de movimiento
    public const INGRESO = 'ingreso';
    public const PRESTAMO = 'prestamo';
    public const TRANSFERENCIA = 'transferencia';
    public const DEVOLUCION = 'devolucion';
    public const BAJA = 'baja';

    public const ETIQUETAS = [
        self::INGRESO => 'Ingreso',
        self::PRESTAMO => 'Préstamo',
        self::TRANSFERENCIA => 'Transferencia',
        self::DEVOLUCION => 'Devolución',
        self::BAJA => 'Baja',
    ];

    // definicion de columnas para el buscadorUniversal
    protected array $buscador = [
        'item.descripcion',
        'item.codigo_patrimonial',
        'funcionario.nombre',
    ];

    protected $table = "movimientos";

    protected $fillable = [
        'lote_id',
        'item_id',
        'funcionario_id',
        'user_id',
        'tipo_movimiento',
        'movimiento_origen_id',
        'fecha_movimiento',
        'fecha_devolucion_prevista',
        'cantidad',
        'motivo',
        'observaciones',
        'anulado_at',
        'anulado_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
            'fecha_devolucion_prevista' => 'date',
            'anulado_at' => 'datetime',
            'cantidad' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion muchos a uno con la tabla items
    public function item()
    {
        return $this->belongsTo(items::class, 'item_id');
    }

    // relacion muchos a uno con la tabla funcionarios
    public function funcionario()
    {
        return $this->belongsTo(funcionarios::class, 'funcionario_id');
    }

    // usuario que registro el movimiento
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // usuario que anulo el movimiento
    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    // prestamo/transferencia original (para devoluciones y bajas de items entregados)
    public function origen()
    {
        return $this->belongsTo(movimientos::class, 'movimiento_origen_id');
    }

    // devoluciones/bajas registradas contra este movimiento
    public function hijos()
    {
        return $this->hasMany(movimientos::class, 'movimiento_origen_id');
    }

    public function scopeActivos($query)
    {
        return $query->whereNull('anulado_at');
    }

    public function estaAnulado(): bool
    {
        return $this->anulado_at !== null;
    }

    // prestamos y transferencias son los unicos que dejan unidades "afuera"
    public function esEntrega(): bool
    {
        return in_array($this->tipo_movimiento, [self::PRESTAMO, self::TRANSFERENCIA], true);
    }

    // cantidad que todavia no volvio (ni fue dada de baja) de un prestamo/transferencia
    public function cantidadPendiente(): int
    {
        if (!$this->esEntrega() || $this->estaAnulado()) {
            return 0;
        }

        // usa el valor precargado con withSum si existe, para evitar consultas extra
        $resuelto = array_key_exists('hijos_activos_sum_cantidad', $this->attributes)
            ? $this->attributes['hijos_activos_sum_cantidad']
            : $this->hijos()->activos()->sum('cantidad');

        return max(0, $this->cantidad - (int) $resuelto);
    }

    public function estaVencido(): bool
    {
        return $this->tipo_movimiento === self::PRESTAMO
            && $this->fecha_devolucion_prevista !== null
            && $this->fecha_devolucion_prevista->isPast()
            && !$this->fecha_devolucion_prevista->isToday()
            && $this->cantidadPendiente() > 0;
    }

    public function etiquetaTipo(): string
    {
        return self::ETIQUETAS[$this->tipo_movimiento] ?? ucfirst($this->tipo_movimiento);
    }
}
