<?php

namespace Database\Factories;

use App\Models\Producte;
use App\Models\Vendor;
use App\Models\Botiga;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProducteFactory extends Factory
{
    protected $model = Producte::class;

    public function definition()
    {
        return [
            'nom' => $this->faker->word,
            'descripcio' => $this->faker->sentence,
            'preu' => $this->faker->randomFloat(2, 10, 100),
            'stock' => $this->faker->numberBetween(1, 50),
            'imatge' => null,
            'vendor_id' => null,      // assignarem al seeder
            'botiga_id' => null,      // assignarem al seeder
            'categoria' => null,      // assignarem al seeder
            'subcategoria' => null,   // assignarem al seeder
            'importacio_id' => null,
        ];
    }
}
