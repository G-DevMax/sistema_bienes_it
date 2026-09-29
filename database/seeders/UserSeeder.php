<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // creacion de un usuario admin predeterminado para pruebas
        $user = new User();
        $user->name = "prueba1";
        $user->email = "prueba1@gmail.com";
        $user->password = bcrypt("1234");
        $user->rol_id = 1; // id del rol admin
        $user->save();
    }
}
