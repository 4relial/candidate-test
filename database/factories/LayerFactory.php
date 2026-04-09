<?php

namespace Database\Factories;

use App\Models\Layer;
use App\Models\Layup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Layer>
 */
class LayerFactory extends Factory
{
    protected $model = Layer::class;

    public function definition(): array
    {
        return [
            'layup_id' => Layup::factory(),
            'layer_order' => fake()->unique()->numberBetween(1, 999),
            'thickness' => fake()->randomFloat(2, 10, 50),
            'width' => fake()->randomFloat(2, 80, 200),
            'angle' => fake()->randomElement([0, 45, 90]),
        ];
    }
}
