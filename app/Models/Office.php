<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Office extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'latitude',
        'longitude',
        'radius'
    ];

    // Relasi ke tabel Companies 
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
