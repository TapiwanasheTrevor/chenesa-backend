<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sensor extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'imei',
        'sim_number',
        'sim_card_id',
        'model',
        'firmware_version',
        'status',
        'last_seen',
        'battery_level',
        'signal_strength',
        'installation_date',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'installation_date' => 'date',
    ];

    public function tank(): HasOne
    {
        return $this->hasOne(Tank::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(SensorReading::class)->latest('created_at');
    }

    public function simCard(): BelongsTo
    {
        return $this->belongsTo(SimCard::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOffline($query)
    {
        return $query->where('last_seen', '<', now()->subMinutes(30));
    }
}