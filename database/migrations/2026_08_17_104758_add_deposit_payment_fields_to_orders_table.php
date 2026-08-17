<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_mode')->default('full')->after('total');
            $table->decimal('amount_due_now', 10, 2)->default(0)->after('payment_mode');
            $table->decimal('amount_remaining', 10, 2)->default(0)->after('amount_due_now');
            $table->timestamp('balance_collected_at')->nullable()->after('amount_remaining');
        });

        DB::table('orders')->update([
            'amount_due_now' => DB::raw('total'),
            'amount_remaining' => 0,
            'payment_mode' => 'full',
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_mode',
                'amount_due_now',
                'amount_remaining',
                'balance_collected_at',
            ]);
        });
    }
};
