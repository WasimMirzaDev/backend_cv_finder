<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;
    protected $table = 'cv_interviews';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'question_id',
        'audio_path',
        'transcription',
        'evaluation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'evaluation' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status'];

    /**
     * Get the user that owns the interview.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question that was answered in the interview.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function getStatusAttribute()
    {
        if (!is_array($this->evaluation) || 
            !isset($this->evaluation['breakdown']) || 
            !is_array($this->evaluation['breakdown']) ||
            !isset($this->evaluation['breakdown']['total']) ||
            !is_array($this->evaluation['breakdown']['total']) ||
            !isset($this->evaluation['breakdown']['total']['score'])) {
            return 'UNKNOWN';
        }

        $score = $this->evaluation['breakdown']['total']['score'];
        
        if ($score > 70) {
            return 'PASS';
        }
        
        return $score > 40 ? 'NEEDS IMPROVEMENT' : 'FAIL';
    }
}
