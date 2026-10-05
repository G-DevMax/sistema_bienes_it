<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\funcionarios;

class FuncionariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $funcionario = new funcionarios();
        $funcionario->nombre = "Carlitos Bierdemann";
        $funcionario->dependencia_id = 1;
        $funcionario->save();

        $funcionario = new funcionarios();
        $funcionario->nombre = "Juan Perez";
        $funcionario->dependencia_id = 2;
        $funcionario->save();

        $funcionario = new funcionarios();
        $funcionario->nombre = "Nacho Bogarin";
        $funcionario->dependencia_id = 3;
        $funcionario->save();
    }
}
