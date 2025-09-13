<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 admins
        User::factory()
            ->admin()
            ->count(5)
            ->create();

        // Create 5 superadmins
        User::factory()
            ->superadmin()
            ->count(5)
            ->create();
    }
}
