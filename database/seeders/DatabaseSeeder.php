<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            SaasSeeder::class,
            AnnouncementSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
