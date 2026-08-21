<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSpace extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_lot_id',
        'space_number',
        'status',
        'type'
    ];

    public function parkingLot()
    {
        return $this->belongsTo(ParkingLot::class, 'parking_lot_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'space_id');
    }

    public function parkingSessions()
    {
        return $this->hasMany(ParkingSession::class, 'space_id');
    }
}