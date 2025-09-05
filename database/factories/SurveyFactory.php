<?php

namespace Database\Factories;

use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }
}
