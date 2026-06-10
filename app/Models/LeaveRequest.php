<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

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

    public function setAttachmentAttribute($value)
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
                $result = $cloudinary->uploadApi()->upload($path, ['folder' => 'leaves']);
                $this->attributes['attachment'] = $result['secure_url'];
                \Storage::disk('public')->delete($value);
                return;
            }
        }
        $this->attributes['attachment'] = $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}