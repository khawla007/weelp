<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
        'slug',
        'description',
        'feature_image',
        'featured_destination'
    ];

    public function regions()
    {
        // return $this->belongsToMany(Region::class, 'region_country');
        return $this->belongsToMany(Region::class, 'region_country', 'country_id', 'region_id');
    }
    
    // public function cities(): HasMany
    // {
    //     return $this->hasMany(City::class);
    // }

    public function locationDetails() {
        return $this->hasOne(CountryLocationDetail::class);
    }

    public function travelInfo() {
        return $this->hasOne(CountryTravelInfo::class);
    }

    public function seasons() {
        return $this->hasMany(CountrySeason::class);
    }
    
    public function events() {
        return $this->hasMany(CountryEvent::class);
    }

    public function additionalInfo() {
        return $this->hasMany(CountryAdditionalInfo::class);
    }
    
    public function faqs() {
        return $this->hasMany(CountryFaq::class);
    }
    
    public function seo() {
        return $this->hasOne(CountrySeo::class);
    }

    public function states() {
        return $this->hasMany(State::class);
    }
}
