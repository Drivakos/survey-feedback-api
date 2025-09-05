<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SurveyController extends Controller
{
    /**
     * Get all active surveys
     */
    public function index()
    {
        $surveys = Cache::remember('surveys.active', 3600, function () {
            return Survey::active()
                ->select('id', 'title', 'description')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Active surveys retrieved successfully',
            'data' => $surveys
        ]);
    }

    /**
     * Get survey details with questions
     */
    public function show($id)
    {
        $survey = Cache::remember("survey.{$id}", 3600, function () use ($id) {
            return Survey::active()
                ->with(['questions' => function($query) {
                    $query->select('id', 'survey_id', 'type', 'question_text');
                }])
                ->find($id);
        });

        if (!$survey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Survey not found or inactive'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Survey details retrieved successfully',
            'data' => $survey
        ]);
    }

    /**
     * Submit answers to a survey
     */
    public function submit(Request $request, $id)
    {
        $survey = Survey::active()->find($id);

        if (!$survey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Survey not found or inactive'
            ], 404);
        }

        $responder = auth()->user();

        // Validate request structure
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.response' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate question IDs in the same submission
        $submittedQuestionIds = collect($request->answers)->pluck('question_id');
        if ($submittedQuestionIds->count() !== $submittedQuestionIds->unique()->count()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate question IDs found in submission'
            ], 422);
        }

        // Validate that all questions belong to this survey
        $questionIds = $submittedQuestionIds->unique();
        $validQuestions = $survey->questions()->whereIn('id', $questionIds)->count();

        if ($validQuestions !== $questionIds->count()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some questions do not belong to this survey'
            ], 422);
        }

        // Check if responder has already submitted this survey
        $existingAnswers = Answer::where('responder_id', $responder->id)
            ->whereIn('question_id', $questionIds)
            ->exists();

        if ($existingAnswers) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already submitted answers for this survey'
            ], 422);
        }

        // Save answers
        $savedAnswers = [];
        foreach ($request->answers as $answerData) {
            $question = Question::find($answerData['question_id']);

            // Validate answer format based on question type
            if (!$this->validateAnswerFormat($question, $answerData['response'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Invalid answer format for question: {$question->question_text}"
                ], 422);
            }

            $answer = Answer::create([
                'question_id' => $answerData['question_id'],
                'responder_id' => $responder->id,
                'response_data' => $answerData['response'],
            ]);

            $savedAnswers[] = $answer;
        }

        $this->logSurveySubmission($survey, $responder, $savedAnswers);
        // Clear cache after successful submission
        $this->clearSurveyCache($survey->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Survey answers submitted successfully',
            'data' => [
                'survey_id' => $survey->id,
                'answers_count' => count($savedAnswers)
            ]
        ], 201);
    }

    /**
     * Get current logged-in responder details
     */
    public function me()
    {
        $responder = auth()->user();

        return response()->json([
            'status' => 'success',
            'message' => 'Responder details retrieved successfully',
            'data' => $responder
        ]);
    }

    /**
     * Clear survey caches
     */
    private function clearSurveyCache($surveyId = null)
    {
        Cache::forget('surveys.active');

        if ($surveyId) {
            Cache::forget("survey.{$surveyId}");
        }
    }

    /**
     * Log survey submission to JSON file
     */
    private function logSurveySubmission($survey, $responder, $answers)
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
            ],
            'responder' => [
                'id' => $responder->id,
                'email' => $responder->email,
            ],
            'answers' => collect($answers)->map(function ($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'response' => $answer->response_data,
                    'submitted_at' => $answer->created_at->toISOString(),
                ];
            })->toArray(),
            'metadata' => [
                'total_questions' => count($answers),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ]
        ];

        $date = now()->format('Y-m-d');
        $logFile = storage_path("logs/surveys/survey_submissions_{$date}.json");

        // Create directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Append to existing file or create new one
        $existingLogs = [];
        if (file_exists($logFile)) {
            $existingContent = file_get_contents($logFile);
            $existingLogs = json_decode($existingContent, true) ?? [];
        }

        $existingLogs[] = $logData;

        file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Validate answer format based on question type
     */
    private function validateAnswerFormat(Question $question, $response)
    {
        switch ($question->type) {
            case 'text':
                return is_string($response) && strlen($response) > 0 && strlen($response) <= 10000;

            case 'scale':
                return is_numeric($response) && $response >= 1 && $response <= 5;

            case 'multiple_choice':
                $responseStr = (string)$response;
                return in_array($responseStr, ['1', '2', '3', '4', '5']);

            default:
                return false;
        }
    }
}
