<?php

namespace Tests\Unit\Models;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function question_has_fillable_attributes()
    {
        $survey = Survey::factory()->create();
        $question = Question::create([
            'survey_id' => $survey->id,
            'type' => 'text',
            'question_text' => 'What is your feedback?'
        ]);

        $this->assertEquals($survey->id, $question->survey_id);
        $this->assertEquals('text', $question->type);
        $this->assertEquals('What is your feedback?', $question->question_text);
    }

    /** @test */
    public function question_belongs_to_survey()
    {
        $survey = Survey::factory()->create();
        $question = Question::factory()->create(['survey_id' => $survey->id]);

        $this->assertInstanceOf(Survey::class, $question->survey);
        $this->assertEquals($survey->id, $question->survey->id);
    }

    /** @test */
    public function question_has_answers_relationship()
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $question->answers);
        $this->assertCount(1, $question->answers);
        $this->assertEquals($answer->id, $question->answers->first()->id);
    }

    /** @test */
    public function question_valid_types()
    {
        $survey = Survey::factory()->create();
        $validTypes = ['text', 'scale', 'multiple_choice'];

        foreach ($validTypes as $type) {
            $question = Question::factory()->create([
                'survey_id' => $survey->id,
                'type' => $type
            ]);

            $this->assertEquals($type, $question->type);
        }
    }
}
