<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'name',
        'payment_id',
        'type',
        'type_id',
        'sub_id',
        'cus_id',
        'status',
        'trial_ends_at',
        'ends_at',
        'starts_at',
        'cancel_at_period_end'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'sub_id' => 'string',
        'cus_id' => 'string',
        'status' => 'string',
        'cancel_at_period_end' => 'boolean'
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     */
    public function valid(): bool
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /** Determine if the subscription is active. */
    // public function active(): bool
    // {
    //     return $this->stripe_status === 'active' || $this->stripe_status === 'trialing';
    // }

    /**
     * Determine if the subscription is no longer active.
     */
    public function cancelled(): bool
    {
        return !is_null($this->ends_at);
    }

    /**
     * Determine if the subscription is within its trial period.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     */
    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class , 'type_id', 'id');
    }
}
