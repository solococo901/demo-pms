<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'country',
        'currency',
        'timezone',
        'check_in_time',
        'check_out_time',
        'status',
        'channex_property_id',
    ];

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function rooms()
    {
        return $this->hasMany(
            Room::class
        );
    }

    public function guests()
    {
        return $this->hasMany(
            Guest::class
        );
    }


    public function reservations()
    {
        return $this->hasMany(
            Reservation::class
        );
    }   

    public function payments()
    {
        return $this->hasMany(
            Payment::class
        );
    }
}