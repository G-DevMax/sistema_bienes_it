<?php

namespace App\Models\Informes;

use App\Traits\BuscadorUniversal;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class detalles_problemas extends Model
{
    use HasFactory, LogsHistory, BuscadorUniversal;

    protected $table = 'detalles_problemas';

    protected array $buscador = [
        'detalle',
        'tipo_problema',
    ];

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
