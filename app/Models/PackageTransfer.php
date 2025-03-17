<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'transfer_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'include_in_package',
        'pickup_location',
        'dropoff_location',
        'pax',
    ];

    public function schedule()
    {
        return $this->belongsTo(PackageSchedule::class, 'schedule_id');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
