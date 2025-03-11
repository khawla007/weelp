<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'transfer_type',
    ];

    // Relationship with TransferVendorRoute
    public function vendorRoutes()
    {
        return $this->hasOne(TransferVendorRoute::class);
    }

    // Relationship with TransferPricingAvailability
    public function pricingAvailability()
    {
        return $this->hasOne(TransferPricingAvailability::class);
    }

    // Relationship with Media
    public function media()
    {
        return $this->hasMany(TransferMedia::class);
    }

    // Relationship with SEO
    public function seo()
    {
        return $this->hasOne(TransferSeo::class);
    }
}
