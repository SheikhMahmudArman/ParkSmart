<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Find extends Model
{
    protected $fillable = [
        'session_id',
        'reservation_id',
        'amount',
        'reason',
        'issue_date',
        'status',
        'payment_id'
    ];

    protected $casts = [
        'issue_date' => 'datetime',
    ];

    public function parkingSession()
    {
        return $this->belongsTo(ParkingSession::class, 'session_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}