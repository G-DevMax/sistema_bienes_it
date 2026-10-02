<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class estados extends Model
{
    use HasFactory;

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
