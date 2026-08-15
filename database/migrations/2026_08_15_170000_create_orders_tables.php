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
            $table->string('number')->unique();
            $table->string('tracking_token', 64)->unique();
            $table->string('student_id');
            $table->string('full_name');
            $table->string('faculty');
            $table->string('phone');
            $table->string('fulfillment');
            $table->text('address')->nullable();
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_rate_name')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('status');
            $table->foreignId('booking_round_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('qty');
            $table->json('choices');
            $table->timestamps();
        });

        Schema::create('payment_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('checksum')->unique();
            $table->string('verifier_result');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_slips');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
