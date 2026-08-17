<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shift',
        'lot_id',
    ];

    public function parkingLot()
    {
        return $this->belongsTo(ParkingLot::class, 'lot_id');
    }
}