<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'responder_id',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function responder()
    {
        return $this->belongsTo(Responder::class);
    }
}
