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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('order_number')->unique(); 
            $table->decimal('total_amount', 10, 2); 
            $table->string('currency')->default('USD'); 
            $table->string('status')->default('pending'); 
            $table->string('payment_method')->nullable(); 
            $table->string('transaction_id')->nullable(); 
            $table->text('notes')->nullable(); 
            $table->timestamps(); 

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
