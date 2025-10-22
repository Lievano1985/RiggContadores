<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call([
            RoleAndPermissionsSeeder::class,
            UserSeeder::class,
            RegimenSeeder::class,
            ObligacionSeeder::class,
            ActividadEconomicaSeeder::class,
            TareaCatalogoSeeder::class,

        ]);



        // User::factory(10)->create();

        /*    User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */
    }
}
