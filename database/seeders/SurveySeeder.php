<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Survey::create([
            'title' => 'Customer Satisfaction Survey',
            'description' => 'Help us improve our services by sharing your experience',
            'status' => 'active',
        ]);

        Survey::create([
            'title' => 'Product Feedback Survey',
            'description' => 'Tell us what you think about our latest products',
            'status' => 'active',
        ]);

        Survey::create([
            'title' => 'Service Quality Assessment',
            'description' => 'Rate our service quality and provide suggestions',
            'status' => 'inactive',
        ]);
    }
}
