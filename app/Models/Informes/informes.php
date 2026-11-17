<?php

namespace App\Models\Informes;

use App\Models\funcionarios;
use App\Traits\BuscadorUniversal;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class informes extends Model
{
    use HasFactory, LogsHistory, BuscadorUniversal;

    public const TIPOS_PROBLEMA = [
        'Equipo/Dispositivo',
        'Programa/Sistema',
        'Internet',
        'Impresora/Escáner',
        'Cuentas y Accesos',
        'Consulta',
        'Insumos',
    ];

    protected $table = 'informes';

    protected array $buscador = [
        'funcionario.nombre',
        'titulo'
    ];

    // created_at queda fuera de $fillable para que la fecha y hora de registro nunca sea editable
    protected $fillable = [
        'titulo',
        'funcionario_id',
        'tipo_problema',
        'detalle_id',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function funcionario()
    {
        return $this->belongsTo(funcionarios::class, 'funcionario_id');
    }

    public function detalle()
    {
        return $this->belongsTo(detalles_problemas::class, 'detalle_id');
    }

    public function detalleTexto(): string
    {
        return $this->detalle?->detalle ?? 'Otro (especificar)';
    }
}
