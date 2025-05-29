<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Categoria;
use App\Models\Botiga;
use App\Models\Producte;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Usuari de prova
        User::factory()->create([
            'name'     => 'Usuari Demo',
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Venedor de prova
        $vendor = Vendor::factory()->create([
            'name'     => 'Venedor Demo',
            'email'    => 'vendor@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 3. Categories i subcategories
        $categories = Categoria::factory(5)->create();
        // Afegim subcategories a la primera categoria
        Categoria::factory(3)->state([
            'parent_id' => $categories->first()->id,
        ])->create();

        // 4. Botigues: dues botigues pel venedor
        $botiga1 = Botiga::factory()->create([
            'nom'        => 'Botiga Central',
            'vendor_id'  => $vendor->id,
            'address'    => 'C/ Central, 1, Barcelona',
            'latitude'   => 41.3851,
            'longitude'  => 2.1734,
        ]);

        $botiga2 = Botiga::factory()->create([
            'nom'        => 'Botiga Mar',
            'vendor_id'  => $vendor->id,
            'address'    => 'Passeig Marítim, 10, Barcelona',
            'latitude'   => 41.3902,
            'longitude'  => 2.1860,
        ]);

        // 5. Productes: 5 productes per botiga
        foreach ([$botiga1, $botiga2] as $botiga) {
            Producte::factory(5)->state([
                'vendor_id' => $vendor->id,
                'botiga_id' => $botiga->id,
                'categoria' => $categories->random()->id,
            ])->create();
        }
    }
}
