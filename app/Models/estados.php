<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class estados extends Model
{
    use HasFactory, \App\Traits\LogsHistory;

    // ids de los estados predeterminados (ver EstadosSeender)
    public const DISPONIBLE = 1;
    public const TRANSFERIDO = 2;
    public const PRESTADO = 3;
    public const INSERVIBLE = 4;
    public const NO_APLICA = 5;

    protected $table = "estado";

    protected $fillable = [
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion uno a muchos con la tabla items
    public function items()
    {
        return $this->hasMany(items::class);
    }
}
