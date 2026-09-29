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
        $rol->nombre = 'Administrador';
        $rol->save();

        $rol = new rol();
        $rol->nombre = 'Editor';
        $rol->save();

        $rol = new rol();
        $rol->nombre = 'Lector';
        $rol->save();
    }
}
