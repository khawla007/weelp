<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'featured_images', 'featured_activity'
    ];

    protected $casts = [
        'featured_images' => 'array',
        'featured_activity' => 'boolean'
    ];

    public function categories() {
        return $this->hasMany(ActivityCategory::class);
    }

    public function locations() {
        return $this->hasMany(ActivityLocation::class);
    }

    public function attributes() {
        return $this->hasMany(ActivityAttribute::class);
    }

    public function pricing() {
        return $this->hasOne(ActivityPricing::class);
    }

    public function seasonalPricing() {
        return $this->hasMany(ActivitySeasonalPricing::class, 'activity_id');
    }

    public function groupDiscounts() {
        return $this->hasMany(ActivityGroupDiscount::class, 'activity_id');
    }

    public function earlyBirdDiscount() {
        return $this->hasMany(ActivityEarlyBirdDiscount::class, 'activity_id');
    }

    public function lastMinuteDiscount() {
        return $this->hasMany(ActivityLastMinuteDiscount::class, 'activity_id');
    }

    public function promoCodes() {
        return $this->hasMany(ActivityPromoCode::class, 'activity_id');
    }
}
