<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionType extends Model
{
    protected $table = 'cv_questiontypes';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the subcategories for the question type.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class, 'questiontype_id');
    }

    /**
     * Get the questions for the question type.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'questiontype_slug', 'slug');
    }
}
