<?php

namespace Database\Seeders;

use App\Models\rol;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // creacion de los roles predeterminados
        $rol = new rol();
        $rol->nombre = 'Administrador IT';
        $rol->save();

        $rol = new rol();
        $rol->nombre = 'Supervisor IT';
        $rol->save();

        $rol = new rol();
        $rol->nombre = 'Director/Jefe de Área';
        $rol->save();

        $rol = new rol();
        $rol->nombre = 'Funcionario';
        $rol->save();
    }
}
