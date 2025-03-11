<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryMediaGallery extends Model
{

    protected $table = 'itinerary_media_gallery';

    protected $fillable = [
        'itinerary_id', 'url'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
