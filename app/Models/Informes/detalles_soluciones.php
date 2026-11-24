<?php

namespace App\Models\Informes;

use App\Models\Tickets\Ticket;
use App\Traits\BuscadorUniversal;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class detalles_soluciones extends Model
{
    use BuscadorUniversal, HasFactory, LogsHistory;

    protected $table = 'detalles_soluciones';

    protected array $buscador = [
        'solucion',
        'tipo_problema',
    ];

    protected $fillable = [
        'tipo_problema',
        'solucion',
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
        return $this->hasMany(informes::class, 'solucion_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'solucion_id');
    }
}
