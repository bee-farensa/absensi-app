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
        Schema::table('users', function (Blueprint $table) {
        // Menambahkan kolom sesuai dokumen PKL 
        $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
        $table->string('nik')->nullable()->unique(); // Nomor Induk Karyawan 
        $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('position_id')->nullable()->constrained()->onDelete('set null');
        
        // Role sesuai dokumen: superadmin, admin_pt, employee 
        $table->enum('role', ['superadmin', 'admin_pt', 'employee'])->default('employee');
        
        // Untuk simpan data wajah nantinya (Bulan 2-4) [cite: 19, 38]
        $table->jsonb('face_embedding')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
