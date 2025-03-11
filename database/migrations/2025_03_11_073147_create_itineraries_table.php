<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id(); // Auto Increment Primary Key
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('city_id');
            $table->boolean('featured')->default(false);
            $table->boolean('private')->default(false);
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itineraries');
    }
};
