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
        'name',
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

    /**
     * Get the display name for the sensor.
     * Returns the alias (name) if set, otherwise returns the device_id.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->device_id;
    }

    /**
     * Generate a friendly name from IMEI or device_id.
     * Example: IMEI 869080071372702 → DF555-372702
     */
    public function generateFriendlyName(): string
    {
        // If IMEI is available, use last 6 digits
        if ($this->imei) {
            return $this->model . '-' . substr($this->imei, -6);
        }

        // Extract model and serial from device_id
        $parts = explode('-', $this->device_id);

        if (count($parts) >= 3) {
            // Return Model-Serial format
            return $parts[0] . '-' . $parts[2];
        }

        return $this->device_id;
    }

    /**
     * Auto-generate and set a friendly name if not already set.
     */
    public function ensureName(): void
    {
        if (!$this->name) {
            $this->name = $this->generateFriendlyName();
            $this->save();
        }
    }
}