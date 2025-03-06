<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySeasonalPricing extends Model {
    use HasFactory;

    protected $table = 'activity_seasonal_pricing';
    protected $fillable = ['activity_id', 'season_name', 'season_start', 'season_end', 'season_price'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
