<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formula extends Model
{
    protected $fillable = [
        'name',
        'description',
        'expression',
        'unit',
        'variables',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
