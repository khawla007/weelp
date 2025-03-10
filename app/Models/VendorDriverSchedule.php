<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDriverSchedule extends Model {
    use HasFactory;

    protected $table = 'vendor_driver_schedules';

    protected $fillable = ['driver_id', 'vehicle_id', 'date', 'shift', 'time'];

    public function driver() {
        return $this->belongsTo(VendorDriver::class);
    }

    public function vehicle() {
        return $this->belongsTo(VendorVehicle::class);
    }
}
