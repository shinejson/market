<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'feature',
        'product_id',
        'prompt_hash',
        'output',
        'review_status',
        'reviewed_by',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
