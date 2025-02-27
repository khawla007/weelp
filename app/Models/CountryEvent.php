<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'type',
        'date_time',
        'location',
        'description',
    ];

    public function country() {
        return $this->belongsTo(Country::class);
    }
}
