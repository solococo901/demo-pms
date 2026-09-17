<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'property_id',
        'reservation_id',
        'guest_id',
        'code',
        'payment_method',
        'provider',
        'transaction_reference',
        'amount',
        'currency',
        'status',
        'paid_at',
        'notes',
    ];


    protected $casts = [
        'amount' =>
            'decimal:2',

        'paid_at' =>
            'datetime',
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
    | Guest
    |--------------------------------------------------------------------------
    */
    public function guest()
    {
        return $this->belongsTo(
            Guest::class
        );
    }
    public function refunds()
    {
        return $this->hasMany(
            PaymentRefund::class
        );
    }
}