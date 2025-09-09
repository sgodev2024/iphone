<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->numberBetween(1000, 1000000),
            'price_buy' => $this->faker->numberBetween(1000, 1000000),
            'quantity' => $this->faker->numberBetween(0, 100),
            'description' => $this->faker->sentence(4),
            'category_id' => $this->faker->randomElement(['1', '2', '5']),
            'status' => $this->faker->randomElement(['inactive', 'published']),
            'brands_id' => 1,
        ];
    }
}
