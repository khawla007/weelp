<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('place_code');
            $table->string('slug')->unique();

            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            
            $table->text('description')->nullable();
            $table->string('feature_image')->nullable();
            $table->boolean('featured_destination')->default(false);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('places');
    }
};
