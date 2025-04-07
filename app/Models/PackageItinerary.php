<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageItinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'itinerary_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'include_in_package',
    ];

    public function schedule()
    {
        return $this->belongsTo(PackageSchedule::class);
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
