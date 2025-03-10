<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model {
    use HasFactory;

    protected $table = 'vendor_vendors'; // Custom table name

    protected $fillable = ['name', 'description', 'email', 'phone', 'address', 'status'];

    public function routes() {
        return $this->hasMany(VendorRoute::class);
    }

    public function pricingTiers() {
        return $this->hasMany(VendorPricingTier::class);
    }

    public function vehicles() {
        return $this->hasMany(VendorVehicle::class);
    }

    public function drivers() {
        return $this->hasMany(VendorDriver::class);
    }
}
