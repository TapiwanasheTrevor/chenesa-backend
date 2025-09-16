<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'tank_id',
        'name',
        'type',
        'condition',
        'action',
        'is_active',
    ];

    protected $casts = [
        'condition' => 'array',
        'action' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}