<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

class rol extends Model
{
    use HasFactory, \App\Traits\LogsHistory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre'
    ];


    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
