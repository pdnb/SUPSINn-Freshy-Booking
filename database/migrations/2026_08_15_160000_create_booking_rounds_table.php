<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_round_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_round_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_round_product');
        Schema::dropIfExists('booking_rounds');
    }
};
