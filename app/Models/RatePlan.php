<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatePlan extends Model
{
    protected $fillable = [
        'property_id',
        'room_type_id',
        'name',
        'code',
        'base_rate',
        'min_stay',
        'stop_sell',
        'status',
        'channex_rate_plan_id',
        'channex_sell_mode',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'min_stay' => 'integer',
        'stop_sell' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function calendars()
    {
        return $this->hasMany(
            RateCalendar::class
        );
    }
}