<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('auth0_sub')->nullable()->unique()->after('email');
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['auth0_sub']);
            $table->dropColumn(['auth0_sub', 'is_admin']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
