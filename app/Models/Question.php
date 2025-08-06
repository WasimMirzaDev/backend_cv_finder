<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $table = 'cv_questions';

    protected $fillable = [
        'speech',
        'title',
        'unique_id',
        'avatar',
        'video_id',
        'difficulty_slug',
        'questiontype_slug',
        'subcategories_slug',
        'question_number',
    ];

    protected $casts = [
        'question_number' => 'integer',
    ];

    /**
     * Get the difficulty level of the question.
     */
    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(DifficultyLevel::class, 'difficulty_slug', 'slug');
    }

    /**
     * Get the question type of the question.
     */
    public function questionType(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class, 'questiontype_slug', 'slug');
    }

    /**
     * Get the subcategory of the question.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategories_slug', 'slug')
            ->where('questiontype_id', function ($query) {
                $query->select('id')
                    ->from('cv_questiontypes')
                    ->whereColumn('slug', 'cv_questions.questiontype_slug');
            });
    }

    /**
     * Get the full question identifier (e.g., 'HINDT8').
     */
    public function getFullIdentifierAttribute(): string
    {
        return sprintf('%s%s%s%d',
            $this->difficulty_slug,
            $this->questiontype_slug,
            $this->subcategories_slug,
            $this->question_number
        );
    }
}
