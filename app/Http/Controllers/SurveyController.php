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

        $responder = JWTAuth::user();

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

        // Validate that all questions belong to this survey
        $questionIds = collect($request->answers)->pluck('question_id')->unique();
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
                'response_data' => json_encode($answerData['response']),
            ]);

            $savedAnswers[] = $answer;
        }

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
        $responder = JWTAuth::user();

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
     * Validate answer format based on question type
     */
    private function validateAnswerFormat(Question $question, $response)
    {
        switch ($question->type) {
            case 'text':
                return is_string($response) && strlen($response) > 0;

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
