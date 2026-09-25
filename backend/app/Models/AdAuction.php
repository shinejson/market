<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdAuction extends Model
{
    protected $fillable = [
        'slot_type',
        'context_hash',
        'winner_campaign_id',
        'winner_product_id',
        'winner_bid',
        'runner_up_bid',
        'charged_cpc',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'winner_bid' => 'decimal:4',
            'runner_up_bid' => 'decimal:4',
            'charged_cpc' => 'decimal:4',
            'decided_at' => 'datetime',
        ];
    }

    public function winnerCampaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'winner_campaign_id');
    }

    public function winnerProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'winner_product_id');
    }
}
