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
            $table->foreignId('bot_user_id')->constrained('bot_users');
            $table->decimal('total_price');
            $table->string('status')->default('process');
            $table->string('type'); // 'product', 'assembly' или 'admin_assembly'
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->foreignId('delivery_method_id')->constrained('delivery_methods');
            $table->timestamps();
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
