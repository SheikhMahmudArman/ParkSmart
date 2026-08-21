<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'space_id',
        'reservation_date',
        'start_time',
        'end_time',
        'status',
        'payment_status',
        'total_amount',
        'reservation_time'
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'reservation_time' => 'datetime'
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class, 'space_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSession()
    {
        return $this->hasOne(ParkingSession::class, 'reservation_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'reservation_id');
    }
}