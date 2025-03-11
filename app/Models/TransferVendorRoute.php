<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferVendorRoute extends Model
{
    use HasFactory;
    protected $fillable = [
        'transfer_id',
        'vendor_id',
        'route_id',
    ];

    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    // Relationship with Vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Relationship with Vendor Route
    public function route()
    {
        return $this->belongsTo(VendorRoute::class);
    }
}