<?php

namespace Database\Seeders;

use App\Models\categorias;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // creacion de las categorias predeterminadas
        $categoria = new categorias();
        $categoria->nombre = 'Computadora';
        $categoria->save();

        $categoria = new categorias();
        $categoria->nombre = "Notebook";
        $categoria->save();

        $categoria = new categorias();
        $categoria->nombre = "Impresora";
        $categoria->save();

        $categoria = new categorias();
        $categoria->nombre = 'Periferico';
        $categoria->save();

        $categoria = new categorias();
        $categoria->nombre = 'Hardware';
        $categoria->save();
    }
}
