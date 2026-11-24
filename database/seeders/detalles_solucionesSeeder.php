<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\TipoProblema;
use App\Models\Informes\detalles_soluciones as InformesDetalles_soluciones;

class detalles_solucionesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $detalle_solucion = new InformesDetalles_soluciones();

        $detalle_solucion->solucion = 'El equipo estaba desconectado';
        $detalle_solucion->tipo_problema = TipoProblema::EquipoDispositivo;
        $detalle_solucion->save();

        $detalle_solucion = new InformesDetalles_soluciones();
        $detalle_solucion->solucion = 'Se le provebio el mouse solicitado';
        $detalle_solucion->tipo_problema = TipoProblema::Insumos;
        $detalle_solucion->save();

        $detalle_solucion = new InformesDetalles_soluciones();
        $detalle_solucion->solucion = 'Se le provebio el teclado solicitado';
        $detalle_solucion->tipo_problema = TipoProblema::Insumos;
        $detalle_solucion->save();

        $detalle_solucion = new InformesDetalles_soluciones();
        $detalle_solucion->solucion = 'Se le provebio la/las resma/resmas solicitada/solicitadas';
        $detalle_solucion->tipo_problema = TipoProblema::Insumos;
        $detalle_solucion->save();
    }
}
