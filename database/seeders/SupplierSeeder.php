<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()
            ->branchOwned()
            ->orderBy('id')
            ->each(function (Company $company): void {
                Supplier::factory()
                    ->count(2)
                    ->state(['company_id' => $company->id])
                    ->create();
            });
    }
}
