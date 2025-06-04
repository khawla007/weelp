<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_type', 'orderable_id', 'orderable_type',
        'travel_date', 'preferred_time', 'number_of_adults',
        'number_of_children', 'status', 'special_requirements'
    ];

    public function orderable()
    {
        return $this->morphTo();
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function emergencyContact()
    {
        return $this->hasOne(OrderEmergencyContact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

