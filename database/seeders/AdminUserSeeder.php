<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'changeme123');

        if (TeamMember::where('email', $email)->exists()) {
            $this->command->info("Admin user {$email} already exists, skipping.");
            return;
        }

        TeamMember::create([
            'name'                 => env('ADMIN_NAME', 'Admin'),
            'email'                => $email,
            'password'             => Hash::make($password),
            'must_change_password' => true,
            'role'                 => 'lead',
            'is_active'            => true,
        ]);

        $this->command->info("Admin user created: {$email} (password: {$password})");
    }
}

// php artisan db:seed --class=AdminUserSeeder
