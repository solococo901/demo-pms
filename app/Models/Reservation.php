<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'property_id',
        'guest_id',
        'code',
        'source',
        'channel',
        'checked_in_at',
        'checked_out_at',
        'external_reservation_id',
        'channex_booking_id',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'tax_amount',
        'fee_amount',
        'total_amount',
        'paid_amount',
        'special_requests',
        'notes',
        'booked_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
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
    | Primary Guest
    |--------------------------------------------------------------------------
    */
    public function guest()
    {
        return $this->belongsTo(
            Guest::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reservation Rooms
    |--------------------------------------------------------------------------
    */
    public function rooms()
    {
        return $this->hasMany(
            ReservationRoom::class
        );
    }


    public function payments()
    {
        return $this->hasMany(
            Payment::class
        );
    }


    public function refunds()
    {
        return $this->hasMany(
            PaymentRefund::class
        );
    }

    public function folioItems()
    {
        return $this->hasMany(
            FolioItem::class
        );
    }
}