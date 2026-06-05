<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Office extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'is_branch',
        'address',
        'phone_number',
        'latitude',
        'longitude',
        'radius',
        'check_in_time',
        'check_out_time',
    ];

    // Relasi ke tabel Companies
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

