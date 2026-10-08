<?php

namespace App\Traits;

use App\Services\HistorialService;
use Illuminate\Database\Eloquent\Model;

trait LogsHistory
{
    /**
     * Boot the trait and register the model events.
     */
    protected static function bootLogsHistory()
    {
        static::created(function (Model $model) {
            app(HistorialService::class)->registrarCreacion($model);
        });

        static::updated(function (Model $model) {
            $originalCompleto = $model->getOriginal();
            app(HistorialService::class)->registrarActualizacion($model, $originalCompleto);
        });

        static::deleted(function (Model $model) {
            app(HistorialService::class)->registrarEliminacion($model);
        });
    }
}
