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
        Schema::create('tolerance_coefficient_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Test Configuration
            $table->decimal('tolerance_coefficient', 3, 1)->comment('Nilai k yang ditest (0.5, 1.0, 1.2, dll)');
            $table->date('test_date')->comment('Tanggal testing dilakukan');
            $table->string('test_phase')->comment('Phase testing: phase_1, phase_2, phase_3, dll');
            
            // Attendance Data
            $table->enum('attendance_type', ['check_in', 'check_out'])->comment('Tipe absensi');
            $table->time('attendance_time')->comment('Waktu absensi');
            $table->decimal('latitude', 10, 8)->comment('Latitude lokasi absensi');
            $table->decimal('longitude', 11, 8)->comment('Longitude lokasi absensi');
            $table->decimal('gps_accuracy', 5, 2)->comment('GPS Accuracy yang terbaca (meter)');
            
            // Calculation Results
            $table->decimal('distance_to_office', 8, 2)->comment('Jarak dari lokasi ke kantor (meter)');
            $table->decimal('office_radius', 5, 2)->comment('Base radius kantor saat testing');
            $table->decimal('effective_radius', 8, 2)->comment('Effective radius = radius + (k × gps_accuracy)');
            
            // Status
            $table->enum('result', ['accepted', 'rejected'])->comment('Hasil: accepted atau rejected');
            $table->decimal('distance_variance', 8, 2)->nullable()->comment('Selisih jarak: distance - effective_radius (negatif = dalam radius)');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['office_id', 'test_date']);
            $table->index(['user_id', 'test_date']);
            $table->index(['tolerance_coefficient', 'test_date']);
            $table->index(['test_phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tolerance_coefficient_tests');
    }
};
