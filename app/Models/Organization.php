<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'country',
        'contact_email',
        'contact_phone',
        'subscription_status',
        'subscription_plan_id',
    ];

    protected $casts = [
        'type' => 'string',
        'country' => 'string',
        'subscription_status' => 'string',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function waterOrders(): HasMany
    {
        return $this->hasMany(WaterOrder::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AlertRule::class);
    }
}
