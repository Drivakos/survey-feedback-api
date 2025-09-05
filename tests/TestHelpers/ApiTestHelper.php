<?php

namespace Tests\TestHelpers;

use App\Models\Responder;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

trait ApiTestHelper
{
    /**
     * Create and authenticate a user for testing
     */
    protected function createAuthenticatedUser(): Responder
    {
        $user = Responder::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    /**
     * Create a complete survey with questions
     */
    protected function createSurveyWithQuestions(int $questionCount = 3): Survey
    {
        return Survey::factory()
            ->has(Question::factory()->count($questionCount))
            ->create(['status' => 'active']);
    }

    /**
     * Generate valid survey answers for a given survey
     */
    protected function generateValidAnswers(Survey $survey): array
    {
        return $survey->questions->map(function ($question) {
            return [
                'question_id' => $question->id,
                'response' => $this->getValidResponseForQuestion($question)
            ];
        })->toArray();
    }

    /**
     * Get a valid response based on question type
     */
    protected function getValidResponseForQuestion(Question $question): mixed
    {
        return match ($question->type) {
            'text' => 'This is a valid text response',
            'scale' => rand(1, 5),
            'multiple_choice' => (string) rand(1, 5),
            default => 'Default response'
        };
    }

    /**
     * Submit survey answers as authenticated user
     */
    protected function submitSurveyAnswers(Survey $survey, ?array $answers = null, ?Responder $user = null): TestResponse
    {
        if ($user) {
            $this->actingAs($user, 'api');
        }

        $answers = $answers ?? $this->generateValidAnswers($survey);

        return $this->postJson("/api/surveys/{$survey->id}/submit", [
            'answers' => $answers
        ]);
    }

}