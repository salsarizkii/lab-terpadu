<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'amount',
        'discount_request',
        'discount_reason',
        'discount_status',
        'discount_approved',
        'discount_type',
        'discount_percent',
        'discount_nominal',
        'status',
        'payment_proof',
        'keterangan',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    public function discountHistories()
    {
        return $this->hasMany(DiscountHistory::class);
    }
}
