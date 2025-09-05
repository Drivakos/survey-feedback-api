<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Questions for Customer Satisfaction Survey (survey_id: 1)
        Question::create([
            'survey_id' => 1,
            'type' => 'scale',
            'question_text' => 'How satisfied are you with our overall service?',
        ]);

        Question::create([
            'survey_id' => 1,
            'type' => 'text',
            'question_text' => 'What did you like most about our service?',
        ]);

        Question::create([
            'survey_id' => 1,
            'type' => 'multiple_choice',
            'question_text' => 'How likely are you to recommend us to a friend?',
        ]);

        // Questions for Product Feedback Survey (survey_id: 2)
        Question::create([
            'survey_id' => 2,
            'type' => 'scale',
            'question_text' => 'How would you rate the quality of our products?',
        ]);

        Question::create([
            'survey_id' => 2,
            'type' => 'text',
            'question_text' => 'What improvements would you suggest for our products?',
        ]);

        // Questions for Service Quality Assessment (survey_id: 3)
        Question::create([
            'survey_id' => 3,
            'type' => 'scale',
            'question_text' => 'How would you rate our customer service?',
        ]);

        Question::create([
            'survey_id' => 3,
            'type' => 'text',
            'question_text' => 'Please provide any additional comments or suggestions.',
        ]);
    }
}
