<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_name',
        'location',
    ];

    public function parkingSpaces()
    {
        return $this->hasMany(ParkingSpace::class);
    }
}