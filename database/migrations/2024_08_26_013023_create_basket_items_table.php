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
        Schema::create('basket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basket_id')->constrained('baskets');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('assembly_id')->nullable();
            $table->foreignId('component_id')->nullable();
            $table->foreignId('admin_assembly_id')->nullable();
            $table->integer('product_count')->nullable();
            $table->integer('component_count')->nullable();
            $table->decimal('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basket_items');
    }
};
