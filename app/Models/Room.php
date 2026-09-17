<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'housekeeping_status',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Property
    |--------------------------------------------------------------------------
    */
    public function property()
    {
        return $this->belongsTo(
            Property::class
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
}