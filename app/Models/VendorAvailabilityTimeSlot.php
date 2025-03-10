<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorAvailabilityTimeSlot extends Model {
    use HasFactory;

    protected $table = 'vendor_availability_time_slots';

    protected $fillable = ['vendor_id', 'vehicle_id', 'date', 'start_time', 'end_time', 'max_bookings', 'price_multiplier'];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedVehicle() {
        return $this->belongsTo(VendorVehicle::class);
    }

}
