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
        Schema::create('city_seo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('city_id')->unsigned();
            $table->string('meta_title'); // SEO meta title
            $table->text('meta_description'); // SEO meta description
            $table->text('keywords')->nullable(); // Comma-separated keywords
            $table->string('og_image_url')->nullable(); // Open Graph image URL
            $table->string('canonical_url')->nullable(); // Canonical URL
            $table->string('schema_type')->nullable(); // Schema type
            $table->json('schema_data')->nullable(); // JSON-LD schema markup
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_seo');
    }
};
