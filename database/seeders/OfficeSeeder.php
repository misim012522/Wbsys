<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\OfficeSchedule;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'default')->first();
        $offices = [
            ['name' => 'Registrar', 'slug' => 'registrar', 'description' => 'Transcripts, enrollment, records'],
            ['name' => 'Cashier', 'slug' => 'cashier', 'description' => 'Tuition, fees, payments'],
            ['name' => 'Guidance', 'slug' => 'guidance', 'description' => 'Counseling, academic advising'],
            ['name' => 'Clinic', 'slug' => 'clinic', 'description' => 'Health services, medical clearance'],
        ];

        foreach ($offices as $data) {
            $attrs = array_merge($data, [
                'location' => 'Building A',
                'is_active' => true,
                'max_daily_queue' => 100,
                'serving_time_minutes' => 15,
            ]);
            if ($tenant) {
                $attrs['tenant_id'] = $tenant->id;
                $office = Office::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $data['slug']],
                    $attrs
                );
            } else {
                $office = Office::firstOrCreate(
                    ['slug' => $data['slug']],
                    $attrs
                );
            }

            // Mon–Fri 8:00–17:00 (day_of_week 1=Monday ... 5=Friday)
            for ($day = 1; $day <= 5; $day++) {
                OfficeSchedule::firstOrCreate(
                    [
                        'office_id' => $office->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => '08:00',
                        'close_time' => '17:00',
                        'is_active' => true,
                    ]
                );
            }
        }

        // No default user. Officers (e.g. enrollment officer) create their own account via Register and choose their office.
    }
}
