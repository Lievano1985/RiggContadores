<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario super_admin
        $admin = User::firstOrCreate(
            ['email' => 'jl3.lievano@gmail.com'],
            [
                'name' => 'jose luis',
                'password' => Hash::make('12345678'),
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        // === NUEVO USUARIO CONTADOR ===
        $contador = User::firstOrCreate(
            ['email' => 'contador1@contador1'],
            [
                'name' => 'Contador1',
                'password' => Hash::make('12345678'),
            ]
        );

        if (!$contador->hasRole('contador')) {
            $contador->assignRole('contador');
        }
    }
}
