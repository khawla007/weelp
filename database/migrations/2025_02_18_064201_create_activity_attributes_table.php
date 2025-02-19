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
        Schema::create('activity_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->enum('type', ['single_select', 'multi_select', 'text', 'number', 'yes_no']);
            $table->text('description')->nullable();
            $table->json('values')->nullable(); 
            $table->string('default_value')->nullable();
            $table->string('taxonomy')->unique();
            $table->string('post_type')->default('activity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_attributes');
    }
};
