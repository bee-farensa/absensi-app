<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah tipe face_embedding dari TEXT ke JSONB
        // PostgreSQL membutuhkan USING clause untuk cast TEXT -> JSONB
        DB::statement('ALTER TABLE users ALTER COLUMN face_embedding TYPE JSONB USING face_embedding::jsonb');

        // 2. Tambah kolom face_verified di tabel attendances
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'face_verified')) {
                $table->boolean('face_verified')->default(false)->after('pic_out');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan JSONB ke TEXT
        DB::statement('ALTER TABLE users ALTER COLUMN face_embedding TYPE TEXT USING face_embedding::text');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('face_verified');
        });
    }
};
