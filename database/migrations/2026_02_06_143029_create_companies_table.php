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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->double('latitude')->nullable(); // Titik tengah kantor
            $table->double('longitude')->nullable();
            $table->integer('radius')->default(100); // Jarak absen maksimal (meter)
            $table->time('check_in_time')->default('08:00');
            $table->time('check_out_time')->default('17:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
