<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateCalendar extends Model
{
    protected $fillable = [
        'property_id',
        'rate_plan_id',
        'date',
        'rate',
        'min_stay',
        'stop_sell',
        'sync_status',
        'synced_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'min_stay' => 'integer',
        'stop_sell' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(
            Property::class
        );
    }

    public function ratePlan()
    {
        return $this->belongsTo(
            RatePlan::class
        );
    }
}