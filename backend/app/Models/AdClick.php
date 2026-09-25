<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdClick extends Model
{
    protected $fillable = [
        'campaign_id',
        'impression_id',
        'product_id',
        'slot',
        'user_hash',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }
}
