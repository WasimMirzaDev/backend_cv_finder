<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategory extends Model
{
    protected $table = 'cv_subcategories';

    protected $fillable = [
        'questiontype_id',
        'name',
        'slug',
    ];

    /**
     * Get the question type that owns the subcategory.
     */
    public function questionType(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class, 'questiontype_id');
    }

    /**
     * Get the questions for the subcategory.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subcategories_slug', 'slug')
            ->where('questiontype_slug', $this->questionType->slug);
    }
}
