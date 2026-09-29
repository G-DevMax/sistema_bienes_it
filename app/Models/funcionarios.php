<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class funcionarios extends Model
{
    use HasFactory;

    protected $table = "funcionarios";

    protected $fillable = [
        'nombre',
        'dependencia_id',
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
}
