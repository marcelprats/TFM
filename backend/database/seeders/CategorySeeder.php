<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['nom' => 'Electrònica',    'parent_id' => null],
            ['nom' => 'Llibres',        'parent_id' => null],
            ['nom' => 'Roba',           'parent_id' => null],
            ['nom' => 'Mòbils',         'parent_id' => 1],  
            ['nom' => 'Portàtils',      'parent_id' => 1],
            ['nom' => 'Ficció',         'parent_id' => 2],
            ['nom' => 'No ficció',      'parent_id' => 2],
            ['nom' => 'Home',           'parent_id' => 3],
            ['nom' => 'Dona',           'parent_id' => 3],
        ];

        foreach ($categories as $cat) {
            Categoria::firstOrCreate(
                ['nom' => $cat['nom']],
                ['parent_id' => $cat['parent_id']]
            );
        }
    }
}
