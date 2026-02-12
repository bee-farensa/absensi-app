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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Tanggal absen
            $table->time('time_in'); // Jam masuk
            $table->time('time_out')->nullable(); // Jam pulang
            $table->string('lat_in'); // Koordinat masuk
            $table->string('long_in');
            $table->string('lat_out')->nullable(); // Koordinat pulang
            $table->string('long_out')->nullable();
            $table->string('pic_in'); // Foto saat masuk
            $table->string('pic_out')->nullable(); // Foto saat pulang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
