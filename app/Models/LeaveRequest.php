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
    public function setAttachmentAttribute($value)
    {
        if ($value && !str_starts_with((string) $value, 'http')) {
            $path = storage_path('app/public/' . $value);
            if (file_exists($path)) {
                $uploaded = Cloudinary::upload($path, ['folder' => 'leaves']);
                $this->attributes['attachment'] = $uploaded->getSecurePath();
                Storage::disk('public')->delete($value);
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
