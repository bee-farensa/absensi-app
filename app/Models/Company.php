<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'latitude',
        'longitude',
        'radius',
        'check_in_time',
        'check_out_time',
        'theme_color',
        'logo',
    ];
}
