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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            // Menghubungkan divisi ke perusahaan tertentu [cite: 15]
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nama Departemen (IT, HR, dll) [cite: 15]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
