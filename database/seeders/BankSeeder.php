<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $file_path = resource_path('sql/bank.json');
        $data      = json_decode(file_get_contents($file_path));
        $now       = now();

        foreach ($data->RECORDS as $item) {
            $banks[] = [
                'id'         => $item->id,
                'name'       => $item->name,
                'code'       => $item->code,
                'bin'        => $item->bin,
                'shortName'  => $item->shortName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Bank::query()->upsert(
            $banks ?? [],
            ['id'],
            ['name', 'code', 'bin', 'shortName', 'updated_at']
        );
    }
}
