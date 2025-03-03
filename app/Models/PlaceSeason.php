<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceSeason extends Model {
    use HasFactory;

    protected $fillable = [
        'place_id',
        'name',
        'months',
        'weather',
        'activities',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
