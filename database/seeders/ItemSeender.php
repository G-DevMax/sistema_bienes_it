<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\items;

class ItemSeender extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // creacion de los items predeterminados
        $item = new items();
        $item->categoria_id = 2;
        $item->descripcion = "Notebook Dell Latitud 5480";
        $item->codigo_patrimonial = "12001.23.04.2815";
        $item->stock = 1;
        $item->estado_id = 1;
        $item->save();
    }
}
