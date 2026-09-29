<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class movimientos extends Model
{
    use HasFactory;

    protected $table = "movimientos";

    protected $fillable = [
        'item_id',
        'funcionario_id',
        'tipo_movimiento',
        'fecha_movimiento',
        'fecha_devuelto',
        'cantidad',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'datetime',
            'fecha_devuelto' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion muchos a uno con la tabla items
    public function item()
    {
        return $this->belongsTo(items::class);
    }

    // relacion muchos a uno con la tabla funcionarios
    public function funcionario()
    {
        return $this->belongsTo(funcionarios::class);
    }
}
