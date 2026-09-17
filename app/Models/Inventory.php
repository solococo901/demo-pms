<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'property_id',
        'room_type_id',
        'date',
        'availability',
        'sync_status',
        'synced_at',
    ];

    protected $casts = [
        'availability' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(
            Property::class
        );
    }

    public function roomType()
    {
        return $this->belongsTo(
            RoomType::class
        );
    }
}