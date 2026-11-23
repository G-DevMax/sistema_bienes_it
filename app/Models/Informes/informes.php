<?php

namespace App\Models\Informes;

use App\Models\funcionarios;
use App\Models\Tickets\Ticket;
use App\Models\User;
use App\Traits\BuscadorUniversal;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class informes extends Model
{
    use BuscadorUniversal, HasFactory, LogsHistory;

    protected $table = 'informes';

    protected array $buscador = [
        'funcionario.nombre',
        'titulo',
    ];

    // created_at queda fuera de $fillable para que la fecha y hora de registro nunca sea editable
    protected $fillable = [
        'ticket_id',
        'user_id',
        'titulo',
        'funcionario_id',
        'tipo_problema',
        'detalle_id',
        'observacion',
        'imagen_problema',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(funcionarios::class, 'funcionario_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(detalles_problemas::class, 'detalle_id');
    }

    public function vieneDeTicket(): bool
    {
        return $this->ticket_id !== null;
    }

    public function detalleTexto(): string
    {
        return $this->detalle?->detalle ?? 'Otro (especificar)';
    }
}
