<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Announcement::create([
            'title' => 'Welcome to the New Update!',
            'content' => 'This is a demo announcement to test the tenant update system. You can create, update, and delete announcements from the admin panel.',
            'type' => 'success',
            'is_active' => true,
        ]);

        Announcement::create([
            'title' => 'System Maintenance',
            'content' => 'Scheduled maintenance will occur tonight from 10 PM to 12 AM. Please save your work before logging out.',
            'type' => 'warning',
            'is_active' => true,
        ]);
    }
}
