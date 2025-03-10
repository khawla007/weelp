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
        Schema::create('transfer_pricing_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('vendor_pricing_tier_id')->constrained('vendor_pricing_tiers')->onDelete('cascade');
            $table->unsignedBigInteger('vendor_availability_time_slot_id');
            $table->foreign('vendor_availability_time_slot_id', 'fk_vendor_avail_time_slot')
                  ->references('id')->on('vendor_availability_time_slots')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_pricing_availability');
    }
};
