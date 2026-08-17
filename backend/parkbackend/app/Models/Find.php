<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Find extends Model
{
    protected $primaryKey = 'FindID';
    protected $fillable = [
        'SessionID', 'ReservationID', 'Amount', 'Reason',
        'IssueDate', 'Status', 'PaymentID'
    ];
    protected $casts = [
        'IssueDate' => 'datetime',
    ];

    public function parkingSession()
    {
        return $this->belongsTo(ParkingSession::class, 'SessionID', 'SessionID');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'ReservationID', 'ReservationID');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'PaymentID', 'PaymentID');
    }
}