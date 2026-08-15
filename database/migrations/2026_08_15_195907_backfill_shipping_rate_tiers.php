<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipping_rates')
            ->whereNull('tiers')
            ->orderBy('id')
            ->each(function (object $rate): void {
                DB::table('shipping_rates')->where('id', $rate->id)->update([
                    'tiers' => [
                        [
                            'min_qty' => 1,
                            'max_qty' => null,
                            'amount' => $rate->amount,
                        ],
                    ],
                ]);
            });
    }

    public function down(): void
    {
        DB::table('shipping_rates')->update(['tiers' => null]);
    }
};
