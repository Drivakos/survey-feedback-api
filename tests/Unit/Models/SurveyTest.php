<?php

namespace Tests\Unit\Models;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function survey_has_fillable_attributes()
    {
        $survey = Survey::create([
            'title' => 'Test Survey',
            'description' => 'Test Description',
            'status' => 'active'
        ]);

        $this->assertEquals('Test Survey', $survey->title);
        $this->assertEquals('Test Description', $survey->description);
        $this->assertEquals('active', $survey->status);
    }

    /** @test */
    public function survey_has_questions_relationship()
    {
        $survey = Survey::factory()->create();
        $question = Question::factory()->create(['survey_id' => $survey->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $survey->questions);
        $this->assertCount(1, $survey->questions);
        $this->assertEquals($question->id, $survey->questions->first()->id);
    }

    /** @test */
    public function survey_scope_active_returns_only_active_surveys()
    {
        Survey::factory()->create(['status' => 'active']);
        Survey::factory()->create(['status' => 'inactive']);
        Survey::factory()->create(['status' => 'active']);

        $activeSurveys = Survey::active()->get();

        $this->assertCount(2, $activeSurveys);
        $activeSurveys->each(function ($survey) {
            $this->assertEquals('active', $survey->status);
        });
    }

    /** @test */
    public function survey_status_defaults_to_active()
    {
        $survey = Survey::create([
            'title' => 'Test Survey',
            'description' => 'Test Description'
        ]);

        $this->assertEquals('active', $survey->status);
    }
}
