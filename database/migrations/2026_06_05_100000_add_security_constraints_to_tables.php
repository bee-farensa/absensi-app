<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds unique constraints and indexes to prevent race conditions and improve performance.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add unique constraint to prevent duplicate check-ins for same user on same date
            // This prevents race condition where multiple simultaneous requests could create duplicates
            $table->unique(['user_id', 'date'], 'unique_user_date_attendance');
            
            // Add index for faster queries on frequently searched columns
            $table->index(['company_id', 'date'], 'idx_company_date');
            $table->index(['office_id', 'date'], 'idx_office_date');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            // Add index for faster queries on leave request overlaps
            $table->index(['user_id', 'start_date', 'end_date', 'status'], 'idx_leave_overlap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('unique_user_date_attendance');
            $table->dropIndex('idx_company_date');
            $table->dropIndex('idx_office_date');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_leave_overlap');
        });
    }
};
