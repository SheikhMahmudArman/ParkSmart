<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'total_spaces',
        'available_spaces',
        'hourly_rate',
        'type',
        'features'
    ];

    protected $casts = [
        'features' => 'array'
    ];

    public function parkingSpaces()
    {
        return $this->hasMany(ParkingSpace::class, 'parking_lot_id');
    }
}