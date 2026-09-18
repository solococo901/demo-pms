<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannexBookingRevision extends Model
{
    protected $fillable = [
        'property_id',
        'reservation_id',
        'revision_id',
        'channex_booking_id',
        'system_id',
        'revision_status',
        'ota_reservation_code',
        'ota_name',
        'processing_status',
        'payload',
        'error_message',
        'received_at',
        'processed_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
