<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'seller_order_id',
        'variant_id',
        'product_name',
        'sku',
        'unit_price',
        'qty',
        'line_tax',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_tax' => 'decimal:2',
            'options' => 'array',
        ];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
