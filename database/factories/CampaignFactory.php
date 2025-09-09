<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->name(),
            'template_id' => $this->faker->randomElement(['14', '15']),
            'delay_date' => $this->faker->numberBetween(1, 30),
            'status' => $this->faker->randomElement(['0', '1']),
        ];
    }
}
