<?php

namespace Tests\Unit\Models;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Responder;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function answer_has_fillable_attributes()
    {
        $question = Question::factory()->create();
        $responder = Responder::factory()->create();

        $answer = Answer::create([
            'question_id' => $question->id,
            'responder_id' => $responder->id,
            'response_data' => 'Test answer'
        ]);

        $this->assertEquals($question->id, $answer->question_id);
        $this->assertEquals($responder->id, $answer->responder_id);
        $this->assertEquals('Test answer', $answer->response_data);
    }

    /** @test */
    public function answer_belongs_to_question()
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(Question::class, $answer->question);
        $this->assertEquals($question->id, $answer->question->id);
    }

    /** @test */
    public function answer_belongs_to_responder()
    {
        $responder = Responder::factory()->create();
        $answer = Answer::factory()->create(['responder_id' => $responder->id]);

        $this->assertInstanceOf(Responder::class, $answer->responder);
        $this->assertEquals($responder->id, $answer->responder->id);
    }

    /** @test */
    public function answer_stores_response_data()
    {
        $answer = Answer::factory()->create([
            'response_data' => ['Test response']
        ]);

        $this->assertEquals(['Test response'], $answer->response_data);
    }

    /** @test */
    public function answer_can_store_complex_json_data()
    {
        $complexData = [
            'rating' => 5,
            'comments' => 'Great service!',
            'timestamp' => now()->toISOString(),
            'metadata' => [
                'browser' => 'Chrome',
                'platform' => 'Web'
            ]
        ];

        $answer = Answer::factory()->create([
            'response_data' => $complexData
        ]);

        $this->assertEquals($complexData, $answer->response_data);
    }
}
