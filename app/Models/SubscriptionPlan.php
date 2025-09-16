<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'max_tanks',
        'max_users',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_tanks' => 'integer',
        'max_users' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}