<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'office_id',
        'company_id',
        'date',
        'time_in',
        'time_out',
        'lat_in',
        'long_in',
        'lat_out',
        'long_out',
        'pic_in',
        'pic_out',
        'is_late',
        'face_verified',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Ensure company_id matches user's company_id
        static::creating(function ($model) {
            if ($model->user_id) {
                $user = User::find($model->user_id);
                if ($user && $model->company_id !== $user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });

        static::updating(function ($model) {
            if ($model->user_id) {
                $user = User::find($model->user_id);
                if ($user && $model->company_id !== $user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }
}