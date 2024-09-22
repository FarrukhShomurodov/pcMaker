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
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('component_category_id')->constrained('component_categories')->onDelete('cascade');
            $table->foreignId('component_type_id')->constrained('component_types')->onDelete('cascade');
            $table->string('brand');
            $table->integer('quantity');
            $table->integer('price');
            $table->json('photos')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
