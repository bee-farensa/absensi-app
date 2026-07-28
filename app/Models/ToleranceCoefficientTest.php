<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToleranceCoefficientTest extends Model
{
    protected $table = 'tolerance_coefficient_tests';

    protected $fillable = [
        'office_id',
        'user_id',
        'company_id',
        'tolerance_coefficient',
        'test_date',
        'test_phase',
        'attendance_type',
        'attendance_time',
        'latitude',
        'longitude',
        'gps_accuracy',
        'distance_to_office',
        'office_radius',
        'effective_radius',
        'result',
        'distance_variance',
        'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
        'attendance_time' => 'datetime:H:i:s',
        'tolerance_coefficient' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'gps_accuracy' => 'float',
        'distance_to_office' => 'float',
        'office_radius' => 'float',
        'effective_radius' => 'float',
        'distance_variance' => 'float',
    ];

    // Relationships
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
