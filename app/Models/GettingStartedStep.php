<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GettingStartedStep extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'sign_up',
        'first_cv',
        'first_interview',
        'progress_tracker',
        'applied_job',
        'refer_friend',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sign_up' => 'boolean',
        'first_cv' => 'boolean',
        'first_interview' => 'boolean',
        'progress_tracker' => 'boolean',
        'applied_job' => 'boolean',
        'refer_friend' => 'boolean',
    ];

    /**
     * Get the user that owns the getting started steps.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
