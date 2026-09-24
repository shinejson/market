<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSettlement extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'seller_order_id',
        'gross',
        'commission',
        'delivery_fee',
        'refund_amount',
        'net',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'commission' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }
}
