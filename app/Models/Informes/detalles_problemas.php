<?php

namespace App\Models\Informes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class detalles_problemas extends Model
{
    use HasFactory, \App\Traits\LogsHistory;

    protected $table = 'detalles_problemas';

    protected $fillable = [
        'tipo_problema',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function informes()
    {
        return $this->hasMany(informes::class, 'detalle_id');
    }
}
