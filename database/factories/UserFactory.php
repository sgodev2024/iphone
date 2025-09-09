<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => '$2y$10$iU7kdoao42V91SFYllnby.zUlEfMKSwYYVKCW3SMvb5vry4kopEFi', // password
            // 'store_name' => $this->faker->randomElement([
            //     'TechStore', 'GadgetShop', 'BookBarn', 'ToyTown', 'FashionHub', 'SportsGear', 'HomeEssentials', 'PetPalace'
            // ]),
            // 'domain' => $this->faker->domainName(),
            // 'company_name' => $this->faker->company(),
            // 'tax_code' => $this->faker->regexify('[A-Z0-9]{10}'),
            'address' => $this->faker->address(),
            'role_id' => 2,
            // 'city_id' => $this->faker->numberBetween(1, 63),
            // 'field_id' => $this->faker->numberBetween(1, 12),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
}
