<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'PaymentID';
    protected $fillable = [
        'ReservationID', 'SessionID', 'Amount', 'PaymentDate',
        'Method', 'Status', 'TransactionID'
    ];
    protected $casts = [
        'PaymentDate' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'ReservationID', 'ReservationID');
    }

    public function parkingSession()
    {
        return $this->belongsTo(ParkingSession::class, 'SessionID', 'SessionID');
    }

    public function finds()
    {
        return $this->hasMany(Find::class, 'PaymentID', 'PaymentID');
    }
}