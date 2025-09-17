<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tank extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'sensor_id',
        'name',
        'location',
        'latitude',
        'longitude',
        'capacity_liters',
        'height_mm',
        'diameter_mm',
        'shape',
        'material',
        'installation_height_mm',
        'low_level_threshold',
        'critical_level_threshold',
        'refill_enabled',
        'auto_refill_threshold',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity_liters' => 'integer',
        'height_mm' => 'integer',
        'diameter_mm' => 'integer',
        'installation_height_mm' => 'integer',
        'low_level_threshold' => 'integer',
        'critical_level_threshold' => 'integer',
        'refill_enabled' => 'boolean',
        'auto_refill_threshold' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(SensorReading::class)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
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

    public function getCurrentLevelAttribute(): float
    {
        return $this->latestReading?->water_level_percentage ?? 0;
    }

    public function getCurrentVolumeAttribute(): float
    {
        return $this->latestReading?->volume_liters ?? 0;
    }

    public function getStatusAttribute(): string
    {
        $currentLevel = $this->getCurrentLevelAttribute();
        $criticalThreshold = $this->critical_level_threshold ?? 10;
        $lowThreshold = $this->low_level_threshold ?? 20;

        if ($currentLevel <= $criticalThreshold) {
            return 'critical';
        }

        if ($currentLevel <= $lowThreshold) {
            return 'low';
        }

        return 'normal';
    }
}