<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_category_id')->constrained('component_categories')->cascadeOnDelete();
            $table->foreignId('compatible_category_id')->constrained('component_categories')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_compatibilities');
    }
};
