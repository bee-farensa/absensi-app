<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CATATAN: Kolom-kolom ini sudah ditambahkan manual lewat pgAdmin ke database Neon.
 * File ini hanya untuk dokumentasi schema, JANGAN dijalankan dengan `php artisan migrate`
 * di database yang sama, karena akan error "column already exists".
 * Berguna kalau nanti perlu setup ulang project di device/environment lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->float('tolerance_coefficient')->default(1.0)->after('radius');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->float('accuracy_in')->nullable()->after('long_in');
            $table->float('accuracy_out')->nullable()->after('long_out');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('tolerance_coefficient');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['accuracy_in', 'accuracy_out']);
        });
    }
};