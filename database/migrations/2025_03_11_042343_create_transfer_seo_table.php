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
        Schema::create('transfer_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->onDelete('cascade');
            $table->string('meta_title', 255);
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->text('og_image_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('schema_type', 255)->nullable();
            $table->json('schema_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_seo');
    }
};
