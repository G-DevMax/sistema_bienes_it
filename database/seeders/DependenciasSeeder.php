<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dependencia;
use App\Models\dependencias;

class DependenciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dependencia = new dependencias();
        $dependencia->nombre = "Departamento de Informática y TICs";
        $dependencia->save();

        $dependencia = new dependencias();
        $dependencia->nombre = "Direccion General de Gabinete";
        $dependencia->save();

        $dependencia = new dependencias();
        $dependencia->nombre = "Departamento de Mantenimiento de Infraestructura";
        $dependencia->save();
    }
}
