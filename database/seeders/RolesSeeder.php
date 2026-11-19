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
            rol::ADMIN => 'Administrador IT',
            rol::SUPERVISOR => 'Supervisor IT',
            rol::DIRECTOR => 'Director/Jefe de Área',
            rol::FUNCIONARIO => 'Funcionario',
            rol::TECNICO_TERCERIZADO => 'Técnico Tercerizado',
        ];

        foreach ($roles as $id => $nombre) {
            rol::firstOrCreate(['id' => $id], ['nombre' => $nombre]);
        }
    }
}
