<?php

namespace Database\Factories;

use App\Models\Responder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ResponderFactory extends Factory
{
    protected $model = Responder::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
        ];
    }
}
