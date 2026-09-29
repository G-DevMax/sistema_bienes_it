<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class historial_cambios extends Model
{
    use HasFactory;

    protected $table = "historial_cambios";

    protected $fillable = [
        'item_id',
        'user_id',
        'cambio',
        'fecha_cambio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // relacion muchos a uno con la tabla items
    public function item()
    {
        return $this->belongsTo(items::class);
    }

    // relacion muchos a uno con la tabla users
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
