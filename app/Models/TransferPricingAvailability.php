<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferPricingAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'vendor_pricing_tier_id',
        'vendor_availability_id',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function pricingTier()
    {
        return $this->belongsTo(VendorPricingTier::class, 'vendor_pricing_tier_id');
    }

    public function availability()
    {
        return $this->belongsTo(VendorAvailability::class, 'vendor_availability_id');
    }
}
