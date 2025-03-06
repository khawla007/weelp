<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPricing extends Model {
    use HasFactory;

    protected $table = 'activity_pricing';
    protected $fillable = [
        'activity_id', 'base_price', 'currency', 
        'enable_seasonal_pricing', 'enable_early_bird_discount', 'enable_last_minute_discount'
    ];

    protected $casts = [
        'enable_seasonal_pricing' => 'boolean',
        'enable_early_bird_discount' => 'boolean',
        'enable_last_minute_discount' => 'boolean'
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
