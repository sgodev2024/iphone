<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckDetail>
 */
class CheckDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(13,26),
            'check_inventory_id' => $this->faker->numberBetween(3,12),
            'difference' => $this->faker->numberBetweeN(-10, 10),
        ];
    }
}
