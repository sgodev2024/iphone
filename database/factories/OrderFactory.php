<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'client_id' => Client::query()->inRandomOrder()->value('id'),
            'total_money' => $this->faker->randomFloat(2, 100000, 10000000),
            'status' => $this->faker->randomElement(['0', '1']), //1 là pending 2 là completed
            'note' => $this->faker->sentence,
            'receive_address' => $this->faker->address,
            'notification' => 1,
        ];
    }
}
