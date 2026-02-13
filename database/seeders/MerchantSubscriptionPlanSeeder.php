<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantSubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'badge_color' => 'secondary',
                'badge_text' => null,
                'short_description' => 'Perfect for startups and small businesses',
                'description' => 'Get started with essential features for your small business',
                'monthly_price' => 29.00,
                'yearly_price' => 23.00,
                'yearly_total' => 276.00,
                'features' => [
                    'Up to 100 customers',
                    'Basic analytics',
                    'Email support',
                    '5 team members',
                    '2GB storage'
                ],
                'sort_order' => 1,
                'is_popular' => false,
                'is_active' => true
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'badge_color' => 'primary',
                'badge_text' => 'Most Popular',
                'short_description' => 'Best for growing businesses',
                'description' => 'Everything you need to scale your business',
                'monthly_price' => 79.00,
                'yearly_price' => 63.00,
                'yearly_total' => 756.00,
                'features' => [
                    'Up to 500 customers',
                    'Advanced analytics',
                    'Priority support',
                    '15 team members',
                    '10GB storage',
                    'API access'
                ],
                'sort_order' => 2,
                'is_popular' => true,
                'is_active' => true
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'badge_color' => 'dark',
                'badge_text' => null,
                'short_description' => 'For large enterprises',
                'description' => 'Advanced features for enterprise-level needs',
                'monthly_price' => 199.00,
                'yearly_price' => 159.00,
                'yearly_total' => 1908.00,
                'features' => [
                    'Unlimited customers',
                    'Real-time analytics',
                    '24/7 phone support',
                    'Unlimited team members',
                    '50GB storage',
                    'Advanced API access',
                    'Custom integrations'
                ],
                'sort_order' => 3,
                'is_popular' => false,
                'is_active' => true
            ]
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
