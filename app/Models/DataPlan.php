<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'provider',
        'data_amount_mb',
        'validity_days',
        'cost',
        'currency',
        'plan_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'data_amount_mb' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the recharge history for this data plan
     */
    public function rechargeHistory(): HasMany
    {
        return $this->hasMany(RechargeHistory::class);
    }

    /**
     * Get formatted data amount
     */
    public function getFormattedDataAmount(): string
    {
        $mb = $this->data_amount_mb;

        if ($mb >= 1024) {
            return number_format($mb / 1024, 2) . ' GB';
        }

        return number_format($mb, 2) . ' MB';
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCost(): string
    {
        return $this->currency . ' ' . number_format($this->cost, 2);
    }

    /**
     * Scope to get only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
