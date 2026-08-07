<?php

namespace Database\Seeders;

use App\Models\ProductStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductStorage::factory()->count(20)->create();
    }
}
