<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Cek dulu, kalau theme_color belum ada, baru buat
            if (!Schema::hasColumn('companies', 'theme_color')) {
                $table->string('theme_color')->default('#2ecc71')->after('name');
            }

            // Cek dulu, kalau logo belum ada, baru buat
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('theme_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            //
        });
    }
};
