<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\estados;

class EstadosSeender extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Estados predeterminados
        $estado = new estados();
        $estado->estado = "Disponible";
        $estado->save();

        $estado = new estados();
        $estado->estado = "Transferido";
        $estado->save();

        $estado = new estados();
        $estado->estado = "Prestado";
        $estado->save();

        $estado = new estados();
        $estado->estado = "Inservible";
        $estado->save();

        $estado = new estados();
        $estado->estado = "No Aplica";
        $estado->save();
    }
}
