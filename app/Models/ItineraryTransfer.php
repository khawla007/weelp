<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryTransfer extends Model
{
    protected $fillable = [
        'schedule_id', 'transfer_id', 'start_time', 'end_time', 
        'notes', 'price', 'include_in_package', 
        'pickup_location', 'dropoff_location', 'pax'
    ];

    public function schedule()
    {
        return $this->belongsTo(ItinerarySchedule::class, 'schedule_id');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
