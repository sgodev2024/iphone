<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::query()->each(function (Branch $branch): void {
            Brand::factory()->count(50)->create([
                'branch_id' => $branch->id,
            ]);
        });
    }
}
