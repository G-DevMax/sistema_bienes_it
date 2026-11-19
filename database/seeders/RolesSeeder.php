<?php

namespace Database\Seeders;

use App\Models\rol;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Administrador IT',
            'Supervisor IT',
            'Director/Jefe de Área',
            'Funcionario',
            'Técnico Tercerizado',
        ];

        foreach ($roles as $nombre) {
            rol::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
