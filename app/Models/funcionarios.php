<?php

namespace App\Models;

use App\Models\Bienes\movimientos;
use App\Traits\BuscadorUniversal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class funcionarios extends Model
{
    use \App\Traits\LogsHistory, HasFactory;
    use BuscadorUniversal;

    public const MENSAJE_PENDIENTES = 'Advertencia: Este funcionario tiene prestamos/transferencias pendientes, resuelve primero sus casos antes de desactivarlo';

    // columnas por la cual se aplicara la busqueda
    protected array $buscador = [
        'nombre',
        'dependencia.nombre',
    ];

    protected $table = 'funcionarios';

    protected $fillable = [
        'nombre',
        'dependencia_id',
        'estado_funcionario',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion muchos a uno con la tabla dependencias
    public function dependencia()
    {
        return $this->belongsTo(dependencias::class);
    }

    // relacion uno a muchos con la tabla movimientos
    public function movimientos()
    {
        return $this->hasMany(movimientos::class);
    }

    public function entregasPendientes(): HasMany
    {
        return $this->hasMany(movimientos::class, 'funcionario_id')
            ->whereNull('anulado_at')
            ->whereIn('tipo_movimiento', [movimientos::PRESTAMO, movimientos::TRANSFERENCIA])
            ->whereRaw('movimientos.cantidad > (select coalesce(sum(h.cantidad), 0) from movimientos h where h.movimiento_origen_id = movimientos.id and h.anulado_at is null)');
    }

    public function tieneEntregasPendientes(): bool
    {
        return $this->entregasPendientes()->exists();
    }
}
