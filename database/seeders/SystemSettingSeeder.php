<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::create([
            'key' => 'maintenance_mode',
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Enable maintenance mode to disable public access',
        ]);

        SystemSetting::create([
            'key' => 'default_queue_number',
            'value' => '1',
            'type' => 'integer',
            'description' => 'Default starting queue number for new days',
        ]);

        SystemSetting::create([
            'key' => 'max_queue_per_day',
            'value' => '100',
            'type' => 'integer',
            'description' => 'Maximum number of queue entries allowed per day',
        ]);

        SystemSetting::create([
            'key' => 'welcome_message',
            'value' => 'Welcome to our queue management system',
            'type' => 'string',
            'description' => 'Welcome message displayed on the public queue page',
        ]);
    }
}
