<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites');
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            // Shipping address
            $table->string('shipping_full_name');
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_country');
            // Payment
            $table->enum('payment_method', ['bank_transfer'])->default('bank_transfer');
            $table->timestamps();

            $table->index(['user_id', 'site_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
