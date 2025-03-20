<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityEarlyBirdDiscount extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'enable_early_bird_discount', 'days_before_start', 'discount_amount', 'discount_type'];

    protected $casts = [
        'enable_early_bird_discount' => 'boolean'
    ];
    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
