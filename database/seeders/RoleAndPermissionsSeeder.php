<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles
        $roles = ['super_admin', 'admin_despacho','supervisor', 'contador', 'cliente'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Crear usuario Super Admin
        $user = User::firstOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'), // Cambia la contraseña después
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('super_admin');
    }
}

/* namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RoleAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
   
    public function run(): void
    {


        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web'
        ]);

        $client = Role::firstOrCreate([
            'name' => 'Client',
            'guard_name' => 'web'
        ]);

        // Crear permisos y asignarlos
        Permission::firstOrCreate(['name' => 'create event', 'guard_name' => 'web'])->assignRole($admin);
        Permission::firstOrCreate(['name' => 'show event', 'guard_name' => 'web'])->syncRoles([$admin, $client]);
        Permission::firstOrCreate(['name' => 'edit event', 'guard_name' => 'web'])->assignRole($admin);

    }



}
 */  