<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $user = User::create([
            'id'       => 1,
            'name'     => 'Super Admin',
            'username' => 'superadmin',
            'email'    => 'superadmin@example.com',
            'password' => 'Admin@1234',
        ]);

        $user->assignRole('super-admin');
    }
}
