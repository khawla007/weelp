<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferPricingAvailability extends Model
{

    use HasFactory;
    protected $fillable = [
        'transfer_id',
        'pricing_tier_id',
        'availability_id',
    ];

    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    // Relationship with VendorPricingTier
    public function pricingTier()
    {
        return $this->belongsTo(VendorPricingTier::class);
    }

    // Relationship with VendorAvailabilityTimeSlot
    public function availability()
    {
        return $this->belongsTo(VendorAvailabilityTimeSlot::class);
    }
}