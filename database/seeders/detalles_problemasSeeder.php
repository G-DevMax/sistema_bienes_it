<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Informes\detalles_problemas as InformesDetalles_problemas;
use App\Enums\TipoProblema;

class detalles_problemasSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $detalle_problema = new InformesDetalles_problemas();
        $detalle_problema->detalle = "Mi computadora no enciende";
        $detalle_problema->tipo_problema = TipoProblema::EquipoDispositivo;
        $detalle_problema->save();

        $detalle_problema = new InformesDetalles_problemas();
        $detalle_problema->detalle = "Solicito un teclado";
        $detalle_problema->tipo_problema = TipoProblema::Insumos;
        $detalle_problema->save();

        $detalle_problema = new InformesDetalles_problemas();
        $detalle_problema->detalle = "Solicito un mouse";
        $detalle_problema->tipo_problema = TipoProblema::Insumos;
        $detalle_problema->save();

        $detalle_problema = new InformesDetalles_problemas();
        $detalle_problema->detalle = "Solicito Resmas (Hojas A4)";
        $detalle_problema->tipo_problema = TipoProblema::Insumos;
        $detalle_problema->save();

        $detalle_problema = new InformesDetalles_problemas();
        $detalle_problema->detalle = "Solicito Resmas (Hojas Oficio)";
        $detalle_problema->tipo_problema = TipoProblema::Insumos;
        $detalle_problema->save();
    }
}
