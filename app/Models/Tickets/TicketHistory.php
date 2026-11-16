<?php

namespace App\Models\Tickets;

use App\Models\funcionarios;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    protected $table = 'ticket_history';

    protected $fillable = [
        'ticket_id',
        'funcionario_id',
        'accion',
        'responsable_id',
        'fecha',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(funcionarios::class, 'funcionario_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
