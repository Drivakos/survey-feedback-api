<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Responder;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition()
    {
        return [
            'question_id' => Question::factory(),
            'responder_id' => Responder::factory(),
            'response_data' => $this->faker->sentence(),
        ];
    }

    public function textAnswer()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_data' => $this->faker->sentence(),
            ];
        });
    }

    public function scaleAnswer()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_data' => $this->faker->numberBetween(1, 5),
            ];
        });
    }

    public function multipleChoiceAnswer()
    {
        return $this->state(function (array $attributes) {
            return [
                'response_data' => (string) $this->faker->numberBetween(1, 5),
            ];
        });
    }
}
