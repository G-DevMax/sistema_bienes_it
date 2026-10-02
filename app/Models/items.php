<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class items extends Model
{
    use HasFactory;

    protected $table = "items";

    protected $fillable = [
        'categoria_id',
        'descripcion',
        'codigo_patrimonial',
        'stock',
        'estado_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion muchos a uno con la tabla categorias
    public function categoria()
    {
        return $this->belongsTo(categorias::class);
    }

    // relacion uno a muchos con la tabla movimientos
    public function movimientos()
    {
        return $this->hasMany(movimientos::class);
    }

    // relacion muchos a uno con la tabla estado
    public function estado()
    {
        return $this->belongsTo(estados::class);
    }
}
