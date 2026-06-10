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

    public function setLogoAttribute($value)
    {
        if ($value && !str_starts_with((string) $value, 'http')) {
            $path = storage_path('app/public/' . $value);
            if (file_exists($path)) {
                $cloudinary = new \Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                        'api_key'    => env('CLOUDINARY_API_KEY'),
                        'api_secret' => env('CLOUDINARY_API_SECRET'),
                    ],
                ]);
                $result = $cloudinary->uploadApi()->upload($path, ['folder' => 'company-logos']);
                $this->attributes['logo'] = $result['secure_url'];
                \Storage::disk('public')->delete($value);
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