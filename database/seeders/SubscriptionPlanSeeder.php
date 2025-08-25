<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started with social media management',
                'price' => 0.00,
                'type' => 'month',
                'duration'=> 2,
                'features' => [
                    '1 social account per platform',
                    '10 posts per month',
                    'Basic analytics',
                    'Content validation',
                ],
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Great for small businesses and content creators',
                'price' => 19.99,
                'type' => 'month',
                'duration'=> 1,
                'features' => [
                    '2 social accounts per platform',
                    '50 posts per month',
                    'Advanced analytics',
                    'Content scheduling',
                    'Priority support',
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Perfect for growing businesses and agencies',
                'price' => 49.99,
                'type' => 'month',
                'duration'=> 2,
                'features' => [
                    '5 social accounts per platform',
                    '200 posts per month',
                    'Advanced analytics & reporting',
                    'Bulk content upload',
                    'Team collaboration',
                    'Priority support',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with extensive social media needs',
                'price' => 199.99,
                'type' => 'month',
                'duration'=> 4,
                'features' => [
                    '20 social accounts per platform',
                    '1000 posts per month',
                    'Custom analytics & reporting',
                    'White-label solution',
                    'Dedicated account manager',
                    'Custom integrations',
                    '24/7 priority support',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Create subscription features
            foreach ($features as $feature) {
                PlanFeature::updateOrCreate([
                    'plan_id' => $plan->id,
                    'features' => $feature,
                ], [
                    'description' => $feature,
                ]);
            }
        }
    }
}
