<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            EstadosSeender::class,
            DependenciasSeeder::class,
            CategoriasSeeder::class,
            FuncionariosSeeder::class,
            UserSeeder::class, // antes de items: el ingreso inicial registra al usuario
            ItemSeender::class,
        ]);
    }
}
