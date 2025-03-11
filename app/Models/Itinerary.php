<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'city_id', 'featured', 'private'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Schedule relation
    public function schedules()
    {
        return $this->hasMany(ItinerarySchedule::class);
    }

    // Base pricing relation
    public function basePricing()
    {
        return $this->hasOne(ItineraryBasePricing::class);
    }

    // Inclusion/Exclusion relation
    public function inclusionsExclusions()
    {
        return $this->hasMany(ItineraryInclusionExclusion::class);
    }

    // Media Gallery relation
    public function mediaGallery()
    {
        return $this->hasMany(ItineraryMediaGallery::class);
    }

    // SEO relation
    public function seo()
    {
        return $this->hasOne(ItinerarySeo::class);
    }

    // Category relation
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'itinerary_categories');
    }

    // Attribute relation
    public function attributes()
    {
        return $this->hasMany(ItineraryAttribute::class);
    }

    // Tag relation
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'itinerary_tags');
    }
}
