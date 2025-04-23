<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryInclusionExclusion extends Model
{

    protected $table = 'itinerary_inclusions_exclusions';

    protected $fillable = [
        'itinerary_id', 'type', 'title', 
        'description', 'include_exclude'
    ];

    protected $casts = [
        'include_exclude' => 'boolean'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
