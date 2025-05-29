<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition()
    {
        $name = $this->faker->unique()->word();

        return [
            'nom'       => ucfirst($name),
            'slug'      => Str::slug($name),
            'parent_id' => null,  // o $this->faker->numberBetween(1, 5) per subcategories
        ];
    }
}
