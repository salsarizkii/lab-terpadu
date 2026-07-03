<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'api_token',
        'is_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected $appends = ['permissions'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function getPermissionsAttribute(): array
    {
        return $this->roleModel?->permissions?->pluck('name')->toArray() ?? [];
    }

    public function beritaAcaras()
    {
        return $this->hasMany(BeritaAcara::class);
    }

    public function assignedBeritaAcaras()
    {
        return $this->hasMany(BeritaAcara::class, 'analyst_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'superadmin') return true;
        return in_array($permission, $this->permissions);
    }
}

