<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceEvent extends Model {
    use HasFactory;

    protected $fillable = [
        'place_id',
        'name',
        'type',
        'date_time',
        'location',
        'description',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
