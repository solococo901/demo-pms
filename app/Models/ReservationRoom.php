<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationRoom extends Model
{
    protected $fillable = [
        'reservation_id',
        'room_type_id',
        'rate_plan_id',
        'room_id',
        'check_in',
        'check_out',
        'adults',
        'children',
        'nightly_rate',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'nightly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',

        'adults' => 'integer',
        'children' => 'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | Reservation
    |--------------------------------------------------------------------------
    */
    public function reservation()
    {
        return $this->belongsTo(
            Reservation::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Room Type
    |--------------------------------------------------------------------------
    */
    public function roomType()
    {
        return $this->belongsTo(
            RoomType::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Rate Plan
    |--------------------------------------------------------------------------
    */
    public function ratePlan()
    {
        return $this->belongsTo(
            RatePlan::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Physical Room
    |--------------------------------------------------------------------------
    */
    public function room()
    {
        return $this->belongsTo(
            Room::class
        );
    }
}