<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Roles
        $adminRole = Role::create(['name' => 'admin']);
        $librarianRole = Role::create(['name' => 'librarian']);
        $memberRole = Role::create(['name' => 'member']);

        // 2. Create your first "Super Admin" account
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@library.com',
            'password' => Hash::make('password'), // Change this later!
        ]);

        // 3. Assign the role
        $admin->assignRole($adminRole);
    }
}