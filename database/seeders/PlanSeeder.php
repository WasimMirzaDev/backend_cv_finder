<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::truncate(); // Clears the table to avoid duplicates on re-seed

        Plan::create([
            'title' => 'Weekly Plan',
            'subdesc' => 'Ideal for short-term projects.',
            'price' => 5.00,
            'interval' => 'weekly',
            'features' => ['Access to all features for 7 days'],
        ]);

        Plan::create([
            'title' => 'Monthly Plan',
            'subdesc' => 'Our most popular plan.',
            'price' => 15.00,
            'interval' => 'monthly',
            'features' => ['Access to all features', 'Priority support'],
        ]);

        Plan::create([
            'title' => 'Quarterly Plan',
            'subdesc' => 'Save more with a longer commitment.',
            'price' => 35.00,
            'interval' => 'quarterly',
            'features' => ['Access to all features', 'Priority support', 'Early access to new features'],
        ]);
    }
}
