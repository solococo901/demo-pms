<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'property_id',
        'code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'date_of_birth',
        'gender',
        'id_type',
        'id_number',
        'address',
        'notes',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
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
    | Full Name
    |--------------------------------------------------------------------------
    |
    | Cho phép dùng:
    |
    | $guest->full_name
    |
    */
    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name
            . ' '
            . ($this->last_name ?? '')
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