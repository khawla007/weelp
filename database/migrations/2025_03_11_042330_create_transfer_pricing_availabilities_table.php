<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfer_pricing_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->onDelete('cascade');
            $table->foreignId('pricing_tier_id')->constrained('vendor_pricing_tiers')->onDelete('cascade');
            $table->foreignId('availability_id')->constrained('vendor_availability_time_slots')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pricing_availabilities');
    }
};
