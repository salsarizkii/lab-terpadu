<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model
{
    protected $fillable = [
        'type',
        'name',
        'html_content',
        'docx_path',
        'show_kop',
        'show_ttd',
        'show_rincian',
        'catatan',
        'settings',
        'paper_size',
        'orientation',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_kop' => 'boolean',
            'show_ttd' => 'boolean',
            'show_rincian' => 'boolean',
            'margin_top' => 'integer',
            'margin_bottom' => 'integer',
            'margin_left' => 'integer',
            'margin_right' => 'integer',
            'settings' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
