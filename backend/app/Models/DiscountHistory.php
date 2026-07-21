<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountHistory extends Model
{
    protected $fillable = [
        'payment_id',
        'discount_request',
        'discount_approved',
        'discount_reason',
        'status',
        'action',
        'changed_by',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
