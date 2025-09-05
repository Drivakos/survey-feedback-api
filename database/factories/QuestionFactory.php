<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition()
    {
        return [
            'survey_id' => Survey::factory(),
            'type' => $this->faker->randomElement(['text', 'scale', 'multiple_choice']),
            'question_text' => $this->faker->sentence(),
        ];
    }

    public function text()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'text',
            ];
        });
    }

    public function scale()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'scale',
            ];
        });
    }

    public function multipleChoice()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'multiple_choice',
            ];
        });
    }
}
