<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AdSpendEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'kind',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }
}
