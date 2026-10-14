<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class items extends Model
{
    use HasFactory, \App\Traits\LogsHistory, \App\Traits\BuscadorUniversal;

    protected array $buscador = [
        'codigo_patrimonial',
        'descripcion',
        'categoria.nombre',
    ];

    protected $table = "items";

    protected $fillable = [
        'categoria_id',
        'descripcion',
        'codigo_patrimonial',
        'stock_total',
        'stock_disponible',
        'estado_id',
    ];

    protected function casts(): array
    {
        return [
            'stock_total' => 'integer',
            'stock_disponible' => 'integer',
            'estado_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // un item es serializado (unico) cuando tiene codigo patrimonial
    public function esSerializado(): bool
    {
        return !empty($this->codigo_patrimonial);
    }

    // texto a mostrar en las vistas para el codigo patrimonial
    public function codigoMostrar(): string
    {
        return $this->esSerializado() ? $this->codigo_patrimonial : 'No aplica';
    }

    // relacion muchos a uno con la tabla categorias
    public function categoria()
    {
        return $this->belongsTo(categorias::class);
    }

    // relacion uno a muchos con la tabla movimientos
    public function movimientos()
    {
        return $this->hasMany(movimientos::class, 'item_id');
    }

    // relacion muchos a uno con la tabla estado
    public function estado()
    {
        return $this->belongsTo(estados::class);
    }
}
