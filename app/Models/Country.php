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

    public function locationDetails() {
        return $this->hasOne(CountryLocationDetail::class);
    }

    public function travelInfo() {
        return $this->hasOne(CountryTravelInfo::class);
    }
}
