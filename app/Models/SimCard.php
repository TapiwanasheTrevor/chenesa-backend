<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'iccid',
        'phone_number',
        'provider',
        'network_type',
        'status',
        'balance',
        'data_balance_mb',
        'activation_date',
        'expiry_date',
        'last_recharge_date',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'data_balance_mb' => 'decimal:2',
        'activation_date' => 'date',
        'expiry_date' => 'date',
        'last_recharge_date' => 'date',
    ];

    /**
     * Get the organization that owns the SIM card
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the sensors using this SIM card
     */
    public function sensors(): HasMany
    {
        return $this->hasMany(Sensor::class);
    }

    /**
     * Get the recharge history for this SIM card
     */
    public function rechargeHistory(): HasMany
    {
        return $this->hasMany(RechargeHistory::class);
    }

    /**
     * Check if SIM card is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->expiry_date === null || $this->expiry_date->isFuture());
    }

    /**
     * Check if SIM card is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Check if SIM card needs recharge (low balance or low data)
     */
    public function needsRecharge(): bool
    {
        return $this->balance < 10 ||
               ($this->data_balance_mb !== null && $this->data_balance_mb < 100);
    }

    /**
     * Get total recharge amount
     */
    public function getTotalRechargeAmount(): float
    {
        return $this->rechargeHistory()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }
}
