<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'dob' => $this->faker->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
            'phone' => $this->faker->unique()->numerify($this->generateVietnamesePhoneNumber()),
            'zip_code' => $this->faker->postcode,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => $this->faker->address,
        ];
    }
    private function generateVietnamesePhoneNumber()
    {
        $prefixes = ['09', '08', '03', '07', '05'];
        $prefix = $this->faker->randomElement($prefixes);
        return $prefix . $this->faker->numerify('########');
    }
}
