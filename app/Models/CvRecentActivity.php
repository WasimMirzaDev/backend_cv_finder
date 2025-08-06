<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CvResume;
use App\Models\Interview;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvRecentActivity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'type_id',
        'message',
        'metadata',
        'ip_address',
        'user_agent',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'time_ago',
    ];

    /**
     * Get the user that owns the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related resume if type is resume-related.
     */
    // public function resume()
    // {
    //     return $this->belongsTo(CvResume::class, 'type_id')
    //         ->where('type', 'like', 'resume%');
    // }

    /**
     * Scope a query to only include unread activities.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include activities of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the activity as read.
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => $this->freshTimestamp()]);
        }
    }

    /**
     * Get the time ago attribute.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the icon based on activity type.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'resume_created' => 'fa-file-circle-plus',
            'resume_updated' => 'fa-file-pen',
            'resume_deleted' => 'fa-file-circle-xmark',
            'profile_updated' => 'fa-user-pen',
            default => 'fa-bell',
        };
    }
    public function resume()
    {
        return $this->belongsTo(CvResume::class, 'type_id');
    }
    
    public function interview()
    {
        return $this->belongsTo(Interview::class, 'type_id');
    }
    
}
