<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'default')->first();
        $admin = User::where('username', 'admin')->orWhere('role', User::ROLE_ADMIN)->first();

        if ($admin) {
            $admin->update([
                'name' => $admin->name ?: 'Administrator',
                'username' => 'admin',
                'email' => $admin->email ?: 'admin@queueless.local',
                'password' => Hash::make('admin'),
                'role' => User::ROLE_ADMIN,
                'tenant_id' => $tenant?->id,
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ]);
            return;
        }

        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@queueless.local',
            'password' => Hash::make('admin'),
            'role' => User::ROLE_ADMIN,
            'tenant_id' => $tenant?->id,
            'email_verified_at' => now(),
        ]);
    }
}
