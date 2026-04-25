<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Support\CentralPricing;
use Illuminate\Database\Seeder;

/**
 * Seeds central app data such as plans.
 */
class SaasSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
    }

    private function seedPlans(): void
    {
        $allowedSlugs = collect(CentralPricing::plans())->pluck('slug');

        Plan::query()
            ->whereNotIn('slug', $allowedSlugs)
            ->delete();

        foreach (CentralPricing::plans() as $data) {
            Plan::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge(collect($data)->except(['tagline'])->all(), ['is_active' => true])
            );
        }
    }
}
