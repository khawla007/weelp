<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityEarlyBirdDiscount extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'days_before_start', 'discount_amount', 'discount_type'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
