<?php

namespace Database\Seeders;

use App\Models\CheckInventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CheckInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CheckInventory::factory()->count(10)->create();
    }
}
