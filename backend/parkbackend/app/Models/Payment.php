<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'reservation_id',
        'session_id',
        'amount',
        'payment_date',
        'method',
        'status',
        'transaction_id'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function parkingSession()
    {
        return $this->belongsTo(ParkingSession::class, 'session_id');
    }

    public function finds()
    {
        return $this->hasMany(Find::class, 'payment_id');
    }
}