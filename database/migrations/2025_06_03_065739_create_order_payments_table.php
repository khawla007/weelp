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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded']);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'cash']);
            $table->decimal('total_amount', 10, 2);
            $table->boolean('is_custom_amount')->default(false);
            $table->decimal('custom_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
