<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'tenant_id',
        'insight_type',
        'payload',
        'data_fingerprint',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }
}
