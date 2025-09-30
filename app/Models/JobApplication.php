<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job',
        'title',
        'company',
        'cv_created',
        'interview_practice',
        'applied',
        'user_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'job' => 'array',
        'cv_created' => 'boolean',
        'interview_practice' => 'boolean',
        'applied' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'prep',
        'cv_created' => false,
        'interview_practice' => false,
        'applied' => false,
    ];

    /**
     * Get the user that owns the job application.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
