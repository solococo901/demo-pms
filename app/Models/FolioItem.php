<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioItem extends Model
{
    protected $fillable = [
        'property_id',
        'reservation_id',
        'code',
        'category',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'currency',
        'status',
        'posted_at',
        'notes',
    ];


    protected $casts = [
        'quantity' =>
            'integer',

        'unit_price' =>
            'decimal:2',

        'total_amount' =>
            'decimal:2',

        'posted_at' =>
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
}