<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'tank_id',
        'user_id',
        'order_number',
        'volume_liters',
        'price',
        'currency',
        'status',
        'delivery_date',
        'delivery_time_slot',
        'delivery_address',
        'notes',
        'driver_id',
        'delivered_at',
    ];

    protected $casts = [
        'volume_liters' => 'integer',
        'price' => 'decimal:2',
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'WO' . str_pad(
                    WaterOrder::count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}