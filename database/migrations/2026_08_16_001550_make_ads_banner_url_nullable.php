<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads_banners', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('ads_banners')->whereNull('url')->update(['url' => '']);

        Schema::table('ads_banners', function (Blueprint $table) {
            $table->string('url')->nullable(false)->change();
        });
    }
};
