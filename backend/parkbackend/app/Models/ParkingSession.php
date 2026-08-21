<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSession extends Model
{
    protected $fillable = [
        'reservation_id',
        'vehicle_id',
        'space_id',
        'entry_time',
        'exit_time',
        'duration_minutes',
        'hourly_rate',
        'total_cost'
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'space_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'session_id');
    }

    public function finds()
    {
        return $this->hasMany(Find::class, 'session_id');
    }
}