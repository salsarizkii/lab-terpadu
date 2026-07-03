<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $fillable = ['form_template_id', 'label', 'type', 'options', 'is_required', 'sort_order', 'placeholder', 'allow_other'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function template()
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }
}
