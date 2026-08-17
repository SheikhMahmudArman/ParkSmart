<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSession extends Model
{
    protected $primaryKey = 'SessionID';
    protected $fillable = [
        'ReservationID', 'VehicleID', 'SpaceID', 'EntryTime',
        'ExitTime', 'DurationMinutes', 'HourlyRate', 'TotalCost'
    ];
    protected $casts = [
        'EntryTime' => 'datetime',
        'ExitTime' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'ReservationID', 'ReservationID');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'VehicleID', 'VehicleID');
    }

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'SpaceID', 'SpaceID');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'SessionID', 'SessionID');
    }

    public function finds()
    {
        return $this->hasMany(Find::class, 'SessionID', 'SessionID');
    }
}