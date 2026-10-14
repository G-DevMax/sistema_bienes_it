<?php

namespace App\Models;

use App\Traits\BuscadorUniversal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class funcionarios extends Model
{
    use HasFactory, \App\Traits\LogsHistory;
    use BuscadorUniversal;

    // columnas por la cual se aplicara la busqueda
    protected array $buscador = [
        'nombre',
        'dependencia.nombre',
    ];

    protected $table = "funcionarios";

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
}
