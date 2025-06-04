<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_status', 'payment_method',
        'total_amount', 'is_custom_amount', 'custom_amount'
    ];

    protected $casts = [
        'is_custom_amount' => 'boolean'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
