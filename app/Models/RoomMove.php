<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomMove extends Model
{
    protected $fillable = [
        'property_id',
        'reservation_id',
        'reservation_room_id',
        'code',
        'from_room_id',
        'to_room_id',
        'from_room_number',
        'to_room_number',
        'reason',
        'notes',
        'moved_at',
    ];


    protected $casts = [
        'moved_at' => 'datetime',
    ];


    public function property()
    {
        return $this->belongsTo(
            Property::class
        );
    }


    public function reservation()
    {
        return $this->belongsTo(
            Reservation::class
        );
    }


    public function reservationRoom()
    {
        return $this->belongsTo(
            ReservationRoom::class
        );
    }


    public function fromRoom()
    {
        return $this->belongsTo(
            Room::class,
            'from_room_id'
        );
    }


    public function toRoom()
    {
        return $this->belongsTo(
            Room::class,
            'to_room_id'
        );
    }
}