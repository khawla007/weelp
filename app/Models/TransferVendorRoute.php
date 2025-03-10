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

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
