<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'store', 'description' => 'Store owner'],
            ['id' => 2, 'name' => 'admin', 'description' => 'Administrator'],
            ['id' => 3, 'name' => 'staff', 'description' => 'Staff'],
        ]);

        $this->call([
            CitySeeder::class,
            BankSeeder::class,
            ConfigSeeder::class,
            UsersTableSeeder::class,
            ClientSeeder::class,
            OrderSeeder::class,
        ]);
        // $this->call(OrderDetailSeeder::class);
        // $this->call([
        //     UsersTableSeeder::class,
        // ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
