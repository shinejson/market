<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutIdempotency extends Model
{
    protected $table = 'checkout_idempotency';

    protected $fillable = [
        'key',
        'user_id',
        'response',
        'status_code',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
