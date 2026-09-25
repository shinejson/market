<?php

namespace App\Services\Ai;

class AiResult
{
    public function __construct(
        public string $text,
        public float $confidence,
        public int $tokensIn,
        public int $tokensOut,
        public string $provider,
        public ?int $suggestedCategoryId = null,
        public array $meta = [],
    ) {}
}
