<?php

namespace App\Models\Informes;

use App\Models\Tickets\Ticket;
use App\Traits\BuscadorUniversal;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class detalles_problemas extends Model
{
    use BuscadorUniversal, HasFactory, LogsHistory;

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

    public function informes(): HasMany
    {
        return $this->hasMany(informes::class, 'detalle_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'detalles_id');
    }
}
