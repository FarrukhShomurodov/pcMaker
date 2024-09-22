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
        Schema::create('type_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('component_type_id');
            $table->unsignedBigInteger('compatible_type_id');
            $table->timestamps();

            $table->foreign('component_type_id')->references('id')->on('component_types')->onDelete('cascade');
            $table->foreign('compatible_type_id')->references('id')->on('component_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_compatibilities');
    }
};
