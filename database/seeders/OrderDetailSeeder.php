<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrderDetail;
use App\Models\Order;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a Faker instance
        $faker = \Faker\Factory::create();

        // Get all existing order IDs
        $orderIds = Order::pluck('id')->toArray();

        if (empty($orderIds)) {
            $this->command->error('No orders found in the orders table.');
            return;
        }

        // Ensure at least one record for each order_id
        foreach ($orderIds as $orderId) {
            OrderDetail::factory()->create([
                'order_id' => $orderId,
                'product_id' => $faker->randomElement(['12', '13', '15']),
            ]);
        }

        // Generate additional random records
        $uniqueCombinations = [];

        for ($i = 0; $i < 100; $i++) {
            // Generate random records ensuring no duplicate unique combination
            do {
                $order_id = $faker->randomElement($orderIds);
                $product_id = $faker->randomElement(['12', '13', '15']);
                $uniqueCombination = $order_id . '-' . $product_id;
            } while (in_array($uniqueCombination, $uniqueCombinations));

            $uniqueCombinations[] = $uniqueCombination;

            OrderDetail::factory()->create([
                'order_id' => $order_id,
                'product_id' => $product_id,
            ]);
        }
    }
}
