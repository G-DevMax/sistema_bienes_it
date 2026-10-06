<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\MovimientoService;

class ItemSeender extends Seeder
{
    /**
     * Run the database seeds.
     * Se usa el servicio para que cada item tenga su movimiento de ingreso inicial.
     */
    public function run(MovimientoService $servicio): void
    {
        $userId = 1; // usuario admin creado en UserSeeder

        // item serializado (unico por su codigo patrimonial)
        $servicio->crearItem([
            'categoria_id' => 2,
            'descripcion' => "Notebook Dell Latitud 5480",
            'codigo_patrimonial' => "12001.23.04.2815",
        ], $userId);

        // item generico (sin codigo patrimonial => "No aplica"), se controla por cantidad
        $servicio->crearItem([
            'categoria_id' => 4,
            'descripcion' => "Cable HDMI 2m",
            'codigo_patrimonial' => null,
            'stock' => 20,
        ], $userId);
    }
}
