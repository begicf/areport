<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const TOPICS = [
        'plugins' => 'Access to DPM Architect / Validation',
        'reporting' => 'Regulatory reporting with AReport',
        'other' => 'Something else',
    ];

    protected $guarded = [];

    protected $casts = ['mailed' => 'boolean', 'handled_at' => 'datetime'];
}
