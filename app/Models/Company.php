<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'phone_number',
    ];

    // Relationships
    public function offices() { return $this->hasMany(Office::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function positions() { return $this->hasMany(Position::class); }
    public function users() { return $this->hasMany(User::class); }
}