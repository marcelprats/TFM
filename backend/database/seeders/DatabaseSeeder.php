<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Botiga;
use App\Models\Producte;
use App\Models\Categoria;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 5 VENEDORS
        $venedors = collect();
        for ($i = 1; $i <= 5; $i++) {
            $venedors->push(Vendor::create([
                'name'     => "Venedor $i",
                'email'    => "venedor$i@totaki.com",
                'password' => bcrypt('password'),
            ]));
        }

        // 2. 5 COMPRADORS (USUARIS)
        $compradors = collect();
        for ($i = 1; $i <= 5; $i++) {
            $compradors->push(User::create([
                'name'     => "Comprador $i",
                'email'    => "comprador$i@totaki.com",
                'password' => bcrypt('password'),
            ]));
        }

        // 3. 5 CATEGORIES + 2 SUBCATEGORIES PER CADA UNA
        $categories = collect();
        $subcategories = collect();
        for ($i = 1; $i <= 5; $i++) {
            $cat = Categoria::create([
                'nom' => "Categoria $i",
                'slug' => "categoria-$i",
                'parent_id' => null,
            ]);
            $categories->push($cat);

            // 2 subcategories per categoria
            for ($j = 1; $j <= 2; $j++) {
                $sub = Categoria::create([
                    'nom' => "Sub $i.$j",
                    'slug' => "sub-$i-$j",
                    'parent_id' => $cat->id,
                ]);
                $subcategories->push($sub);
            }
        }

        // 4. 9 BOTIGUES (van rotant venedors)
        $botigues = collect();
        for ($i = 1; $i <= 9; $i++) {
            $lat = mt_rand(41350000, 41450000) / 1e6; // 41.350000 a 41.450000
            $lng = mt_rand(2100000, 2220000) / 1e6;   // 2.100000 a 2.220000

            $botigues->push(Botiga::create([
                'nom'        => "Botiga $i",
                'descripcio' => "Descripció botiga $i",
                'vendor_id'  => $venedors[($i-1)%5]->id,
                'address'    => "Adreça botiga $i",
                'latitude'   => $lat,
                'longitude'  => $lng,
            ]));
        }

        // 5. 50 PRODUCTES REPARTITS ENTRE LES 9 BOTIGUES, CATEGORIES I SUBCATEGORIES
        $productesPerBotiga = [7, 6, 5, 9, 3, 8, 2, 6, 4]; // suma: 50
        $prodNum = 1;
        foreach ($botigues as $idx => $botiga) {
            for ($j = 1; $j <= $productesPerBotiga[$idx]; $j++) {
                // assignem categoria i subcategòria de manera cíclica
                $categoria = $categories[($prodNum-1) % $categories->count()];
                $subsCat = $subcategories->where('parent_id', $categoria->id)->values();
                $subcategoria = $subsCat[($j-1) % $subsCat->count()];

                Producte::create([
                    'nom'          => "Producte $prodNum",
                    'descripcio'   => "Descripció del producte $prodNum",
                    'preu'         => rand(10, 100),
                    'stock'        => rand(1, 20),
                    'vendor_id'    => $botiga->vendor_id,
                    'botiga_id'    => $botiga->id,
                    'categoria'    => $categoria->id,
                    'subcategoria' => $subcategoria->id,
                    'imatge'       => null,
                    'importacio_id'=> null,
                ]);
                $prodNum++;
            }
        }
    }
}