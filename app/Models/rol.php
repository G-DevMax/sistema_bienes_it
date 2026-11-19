<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rol extends Model
{
    use \App\Traits\LogsHistory, HasFactory;

    public const ADMIN = 1;

    public const SUPERVISOR = 2;

    public const DIRECTOR = 3;

    public const FUNCIONARIO = 4;

    public const TECNICO_TERCERIZADO = 5;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
