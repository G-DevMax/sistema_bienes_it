<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class dependencias extends Model
{
    use HasFactory, \App\Traits\LogsHistory;

    protected $table = "dependencias";

    protected $fillable = [
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion uno a muchos con la tabla funcionarios
    public function funcionarios()
    {
        return $this->hasMany(funcionarios::class, 'dependencia_id');
    }
}
