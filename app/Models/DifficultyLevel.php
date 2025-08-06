<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DifficultyLevel extends Model
{
    protected $table = 'cv_difficultylevels';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the questions for the difficulty level.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'difficulty_slug', 'slug');
    }
}
