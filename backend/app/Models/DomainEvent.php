<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'event_id',
        'type',
        'payload',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'dispatched_at' => 'datetime',
        ];
    }
}
