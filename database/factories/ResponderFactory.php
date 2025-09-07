<?php

namespace Database\Factories;

use App\Models\Responder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponderFactory extends Factory
{
    protected $model = Responder::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123', // Will be auto-hashed by model mutator
        ];
    }
}
