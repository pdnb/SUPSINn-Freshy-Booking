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
        Schema::table('product_option_groups', function (Blueprint $table) {
            $table->string('depends_on_key')->nullable()->after('label');
            $table->json('depends_on_values')->nullable()->after('depends_on_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_option_groups', function (Blueprint $table) {
            $table->dropColumn(['depends_on_key', 'depends_on_values']);
        });
    }
};
