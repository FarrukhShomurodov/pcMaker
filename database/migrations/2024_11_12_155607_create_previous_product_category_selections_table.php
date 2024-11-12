<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('previous_product_category_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_sub_category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_product_category_selections');
    }
};
