<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'place_code',
        'slug',
        'city_id',
        'description',
        'feature_image',
        'featured_destination',
    ];

    public function locationDetails() {
        return $this->hasOne(PlaceLocationDetail::class);
    }

    public function travelInfo() {
        return $this->hasOne(PlaceTravelInfo::class);
    }

    public function seasons() {
        return $this->hasMany(PlaceSeason::class);
    }

    public function events() {
        return $this->hasMany(PlaceEvent::class);
    }

    public function additionalInfo() {
        return $this->hasMany(PlaceAdditionalInfo::class);
    }

    public function faqs() {
        return $this->hasMany(PlaceFaq::class);
    }

    public function seo() {
        return $this->hasOne(PlaceSeo::class);
    }
}
