<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_usage';

    protected $fillable = [
        'tenant_id',
        'feature',
        'provider',
        'tokens_in',
        'tokens_out',
        'cost_estimate',
        'latency_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_estimate' => 'decimal:6',
        ];
    }
}
