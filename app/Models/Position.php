<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Position extends Model
{
    protected $fillable = ['company_id', 'name', 'level'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
