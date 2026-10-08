<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class categorias extends Model
{
    use HasFactory, \App\Traits\LogsHistory;

    protected $table = "categorias";

    protected $fillable = [
        'nombre',
    ];

    public function items()
    {
        return $this->hasMany(items::class, 'categoria_id');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
