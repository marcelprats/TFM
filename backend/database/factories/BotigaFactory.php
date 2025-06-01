<?php

namespace Database\Factories;

use App\Models\Botiga;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotigaFactory extends Factory
{
    protected $model = Botiga::class;

    public function definition()
    {
        return [
            'nom' => $this->faker->company . ' Store',
            'descripcio' => $this->faker->sentence,
            'vendor_id' => null, // assignarem al seeder
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
    }
}
