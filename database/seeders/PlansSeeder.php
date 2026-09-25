<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Trial',
                'price' => 0,
                'duration' => 1,
                'duration_type' => 'months',
                'description' => 'One month free access to all premium content.',
                'features' => "All paid articles\nMagazine reader access\nFull access for 1 month",
            ],
            [
                'name' => 'Monthly Reader',
                'price' => 99,
                'duration' => 1,
                'duration_type' => 'months',
                'description' => 'Best for readers who want full access month by month.',
                'features' => "All paid articles\nMagazine reader access\nCancel anytime",
            ],
            [
                'name' => 'Quarterly Reader',
                'price' => 249,
                'duration' => 3,
                'duration_type' => 'months',
                'description' => 'A simple plan for regular readers.',
                'features' => "All paid articles\nMagazine reader access\nBetter value than monthly",
            ],
            [
                'name' => 'Annual Reader',
                'price' => 899,
                'duration' => 1,
                'duration_type' => 'years',
                'description' => 'The best value for committed readers.',
                'features' => "All paid articles\nMagazine reader access\nFull year premium archive",
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                $this->payload($plan)
            );
        }
    }

    private function payload(array $plan): array
    {
        $payload = [
            'price' => $plan['price'],
            'duration' => $plan['duration'],
            'duration_type' => $plan['duration_type'],
            'description' => $plan['description'],
        ];

        if (Schema::hasColumn('plans', 'features')) {
            $payload['features'] = $plan['features'];
        }

        if (Schema::hasColumn('plans', 'duration_days')) {
            $payload['duration_days'] = $this->durationDays($plan['duration'], $plan['duration_type']);
        }

        if (Schema::hasColumn('plans', 'status')) {
            $payload['status'] = 1;
        }

        if (Schema::hasColumn('plans', 'is_active')) {
            $payload['is_active'] = 1;
        }

        return $payload;
    }

    private function durationDays(int $duration, string $durationType): int
    {
        return match ($durationType) {
            'years' => $duration * 365,
            'months' => $duration * 30,
            default => $duration,
        };
    }
}
