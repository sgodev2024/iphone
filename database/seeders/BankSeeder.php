<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        if (Bank::query()->first())
            return;

        $file_path = resource_path('sql/bank.json');
        $data      = json_decode(file_get_contents($file_path));
        foreach ($data->RECORDS as $item) {
            $cities[] = [
                'id'         => $item->id,
                'name'       => $item->name,
                'code'       => $item->code,
                'bin'        => $item->bin,
                'shortName'  => $item->shortName
            ];
        }
        Bank::query()->insert($cities ?? []);
    }
}
