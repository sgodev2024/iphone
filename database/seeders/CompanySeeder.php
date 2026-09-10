<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()
            ->orderBy('id')
            ->each(function (Branch $branch): void {
                Company::factory()
                    ->count(3)
                    ->state([
                        'user_id' => $branch->admin_store_user_id ?: $branch->user_id,
                        'branch_id' => $branch->id,
                        'status' => true,
                    ])
                    ->create();
            });
    }
}
