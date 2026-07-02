<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_system',
        'requires_verification',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'requires_verification' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}
