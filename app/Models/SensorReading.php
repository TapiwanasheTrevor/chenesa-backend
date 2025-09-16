<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasUuids;

    protected $fillable = [
        'sensor_id',
        'tank_id',
        'distance_mm',
        'water_level_mm',
        'water_level_percentage',
        'volume_liters',
        'temperature',
        'battery_voltage',
        'signal_rssi',
        'raw_data',
    ];

    protected $casts = [
        'distance_mm' => 'integer',
        'water_level_mm' => 'integer',
        'water_level_percentage' => 'decimal:2',
        'volume_liters' => 'decimal:2',
        'temperature' => 'decimal:2',
        'battery_voltage' => 'decimal:2',
        'signal_rssi' => 'integer',
        'raw_data' => 'array',
    ];

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}