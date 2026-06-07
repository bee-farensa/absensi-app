<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'attachment',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
class Company extends Model
{
    protected $fillable = ['name', 'logo', 'phone_number'];

    public function setLogoAttribute($value)
    {
        if ($value && !str_starts_with((string)$value, 'http')) {
            $path = storage_path('app/public/' . $value);
            if (file_exists($path)) {
                $uploaded = Cloudinary::upload($path, ['folder' => 'company-logos']);
                $this->attributes['logo'] = $uploaded->getSecurePath();
                Storage::disk('public')->delete($value);
                return;
            }
        }
        $this->attributes['logo'] = $value;
    }

    public function offices() { return $this->hasMany(Office::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function positions() { return $this->hasMany(Position::class); }
    public function users() { return $this->hasMany(User::class); }
}