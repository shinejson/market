<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXHAUSTED = 'exhausted';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'objective',
        'status',
        'daily_budget',
        'total_budget',
        'bid_cpc',
        'spent_today',
        'spent_total',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'daily_budget' => 'decimal:2',
            'total_budget' => 'decimal:2',
            'bid_cpc' => 'decimal:4',
            'spent_today' => 'decimal:2',
            'spent_total' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AdTarget::class, 'campaign_id');
    }

    public function remainingBudget(): string
    {
        return bcsub((string) $this->total_budget, (string) $this->spent_total, 2);
    }
}
