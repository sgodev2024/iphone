<?php

namespace Database\Seeders;

use App\Models\CheckDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CheckDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CheckDetail::factory()->count(20)->create();
    }
}
