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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('radius');
            $table->time('check_out_time')->nullable()->after('check_in_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
        });
    }
};
