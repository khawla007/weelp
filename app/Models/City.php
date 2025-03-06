<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'city_code', 'slug', 'state_id', 'description', 
        'feature_image', 'featured_city'
    ];

    // public function country(): BelongsTo
    // {
    //     return $this->belongsTo(Country::class);
    // }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function locationDetails()
    {
        return $this->hasOne(CityLocationDetail::class);
    }

    public function travelInfo()
    {
        return $this->hasOne(CityTravelInfo::class);
    }

    public function seasons()
    {
        return $this->hasMany(CitySeason::class);
    }

    public function events()
    {
        return $this->hasMany(CityEvent::class);
    }

    public function additionalInfo()
    {
        return $this->hasMany(CityAdditionalInfo::class);
    }

    public function faqs()
    {
        return $this->hasMany(CityFaq::class);
    }

    public function seo()
    {
        return $this->hasOne(CitySeo::class);
    }

    public function places() {
        return $this->hasMany(Place::class);
    }

    public function activityLocations() {
        return $this->hasMany(ActivityLocation::class, 'city_id');
    }

    public function activities() {
        return $this->hasManyThrough(Activity::class, ActivityLocation::class, 'city_id', 'id', 'id', 'activity_id');
    }
}
