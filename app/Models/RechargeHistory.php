<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechargeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'sim_card_id',
        'data_plan_id',
        'user_id',
        'recharge_type',
        'amount',
        'currency',
        'data_amount_mb',
        'reference_number',
        'status',
        'recharge_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'data_amount_mb' => 'decimal:2',
        'recharge_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the SIM card that was recharged
     */
    public function simCard(): BelongsTo
    {
        return $this->belongsTo(SimCard::class);
    }

    /**
     * Get the data plan applied
     */
    public function dataPlan(): BelongsTo
    {
        return $this->belongsTo(DataPlan::class);
    }

    /**
     * Get the user who performed the recharge
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if recharge is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if recharge has expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Scope to get only completed recharges
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter by recharge type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('recharge_type', $type);
    }
}
