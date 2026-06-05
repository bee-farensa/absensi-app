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
        Schema::table('offices', function (Blueprint $table) {
            $table->boolean('is_branch')->default(true)->after('name');
        });
        
        // Update data lama: Kita asumsikan kantor pertama yang dibuat adalah Kantor Pusat
        // Ini untuk menjaga kompatibilitas data yang sudah ada.
        $companies = \DB::table('companies')->get();
        foreach ($companies as $company) {
            \DB::table('offices')
                ->where('company_id', $company->id)
                ->orderBy('created_at', 'asc')
                ->limit(1)
                ->update(['is_branch' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('is_branch');
        });
    }
};
