<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryTravelInfo extends Model
{
    use HasFactory;

    protected $table = 'country_travel_info';
    protected $fillable = [
        'country_id',
        'airport',
        'public_transportation',
        'taxi_available',
        'rental_cars_available',
        'hotels',
        'hostels',
        'apartments',
        'resorts',
        'visa_requirements',
        'best_time_to_visit',
        'travel_tips',
        'safety_information'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
