<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate company logos from full URL to Cloudinary path
        DB::table('companies')->whereNotNull('logo')->orderBy('id')->each(function ($company) {
            $logo = $company->logo;
            
            // Jika logo masih berupa full URL Cloudinary
            if (str_starts_with($logo, 'http')) {
                // Extract path dari URL Cloudinary
                // Format URL: https://res.cloudinary.com/{cloud_name}/image/upload/v{version}/{path}
                // Kita ambil bagian {path} saja
                
                if (preg_match('#/image/upload/(?:v\d+/)?(.+)$#', $logo, $matches)) {
                    $path = $matches[1];
                    
                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update(['logo' => $path]);
                    
                    echo "✓ Migrated company #{$company->id}: {$company->name}\n";
                    echo "  Old: {$logo}\n";
                    echo "  New: {$path}\n\n";
                }
            } else {
                echo "→ Company #{$company->id} already has path format: {$logo}\n";
            }
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena path Cloudinary tetap valid
        echo "Rollback skipped - Cloudinary paths are still valid\n";
    }
};