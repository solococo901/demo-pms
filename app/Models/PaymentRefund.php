<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    protected $fillable = [
        'property_id',
        'reservation_id',
        'payment_id',
        'code',
        'amount',
        'currency',
        'refund_method',
        'provider',
        'transaction_reference',
        'reason',
        'status',
        'refunded_at',
        'notes',
    ];

    protected $casts = [
        'amount' =>
            'decimal:2',

        'refunded_at' =>
            'datetime',
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


    public function payment()
    {
        return $this->belongsTo(
            Payment::class
        );
    }
}