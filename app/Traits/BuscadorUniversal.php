<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
/*
    @mixin \Illuminate\Database\Eloquent\Model
    @property array $buscador
*/

trait BuscadorUniversal
{
    // scope dinamico para buscar en las columnas definidas en $buscador de cada modelo
    public function scopeBuscadorUniversal(Builder $query, ?string $term): void
    {
        $term = trim($term ?? '');

        // Si el término está vacío o el modelo no tiene columnas configuradas, no hace nada
        if ($term === '' || empty($this->buscador)) {
            return;
        }

        // Agrupación de las condciones WHERE
        $query->where(function (Builder $subQuery) use ($term) {
            foreach ($this->buscador as $column) {

                // Si la columna incluye un punto (ej: 'departamento.nombre'), busca en la relación
                if (Str::contains($column, '.')) {
                    [$relation, $relationColumn] = explode('.', $column, 2);

                    $subQuery->orWhereHas($relation, function (Builder $relQuery) use ($relationColumn, $term) {
                        $relQuery->where($relationColumn, 'LIKE', "%{$term}%");
                    });
                } else {
                    // Si es una columna normal de la tabla actual, se califica con el nombre de la tabla
                    // para evitar errores de "ambiguous column" si se hacen joins manuales
                    $subQuery->orWhere($this->qualifyColumn($column), 'LIKE', "%{$term}%");
                }
            }
        });
    }
}
