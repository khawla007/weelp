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

    public function vendorRoutes()
    {
        return $this->hasMany(TransferVendorRoute::class);
    }

    public function pricingAvailability()
    {
        return $this->hasOne(TransferPricingAvailability::class);
    }

    public function media()
    {
        return $this->hasMany(TransferMedia::class);
    }

    public function seo()
    {
        return $this->hasOne(TransferSeo::class);
    }
}
