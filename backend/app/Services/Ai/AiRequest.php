<?php

namespace App\Services\Ai;

class AiRequest
{
    public function __construct(
        public string $feature,
        public string $prompt,
        public array $attributes = [],
        public ?string $tone = null,
        public ?string $length = null,
        public array $bannedWords = [],
        public array $categories = [],
    ) {}
}
