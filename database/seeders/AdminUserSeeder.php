<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the initial admin user.
 * Run: php artisan db:seed --class=AdminUserSeeder
 * Default credentials: admin@example.com / changeme
 * !! Change the password immediately after first login !!
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('changeme'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('✓ Admin user created: admin@example.com / changeme');
        $this->command->warn('⚠ Change the password immediately after first login!');
    }
}
