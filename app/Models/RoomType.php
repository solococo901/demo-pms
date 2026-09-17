<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'code',
        'description',
        'total_rooms',
        'max_adults',
        'max_children',
        'base_price',
        'status',
        'channex_room_type_id',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'total_rooms' => 'integer',
        'max_adults' => 'integer',
        'max_children' => 'integer',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function ratePlans()
    {
        return $this->hasMany(RatePlan::class);
    }

    public function rooms()
    {
        return $this->hasMany(
            Room::class
        );
    }
}