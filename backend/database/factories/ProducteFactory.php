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
            'nom'           => ucfirst($this->faker->word),
            'descripcio'    => $this->faker->sentence,
            'preu'          => $this->faker->randomFloat(2, 1, 100),
            'stock'         => $this->faker->numberBetween(0, 50),
            'imatge'        => null,
            // Per defecte enllaça a un vendor i a una botiga nous
            'vendor_id'     => Vendor::factory(),
            'botiga_id'     => Botiga::factory(),
            'importacio_id' => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
