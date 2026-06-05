<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // WAJIB TAMBAH INI
use Filament\Panel; // WAJIB TAMBAH INI
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser // TAMBAHKAN 'implements FilamentUser'
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'company_id',
        'office_id',
        'nik',
        'department_id',
        'position_id',
        'face_embedding',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'face_embedding' => 'array', // Auto encode/decode JSON <-> PHP array
        ];
    }

    /**
     * Method wajib dari FilamentUser untuk memberi izin akses ke panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Untuk tahap awal, izinkan semua user yang punya akun masuk.
        // Nanti jika ingin diperketat, bisa diubah menjadi:
        return $this->hasRole(['super_admin', 'admin_pt']);
        // return true; 
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}