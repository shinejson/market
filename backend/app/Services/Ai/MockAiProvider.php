<?php

namespace App\Services\Ai;

class MockAiProvider implements AiProvider
{
    public function complete(AiRequest $req): AiResult
    {
        $name = (string) ($req->attributes['name'] ?? 'This product');
        $brand = (string) ($req->attributes['brand'] ?? '');
        $attrs = collect($req->attributes)
            ->except(['name', 'brand', 'description'])
            ->filter()
            ->map(fn ($v, $k) => $k.': '.$v)
            ->implode(', ');

        $tone = $req->tone ?: 'warm';
        $lead = match ($tone) {
            'luxury' => 'Crafted for discerning everyday use, ',
            'playful' => 'Meet ',
            default => 'Discover ',
        };

        $text = trim($lead.$name.($brand ? ' by '.$brand : '').'.')
            .' Built from seller-supplied details'
            .($attrs ? ' ('.$attrs.')' : '')
            .'. Quality marketplace listing with tracked inventory.';

        foreach ($req->bannedWords as $word) {
            $text = str_ireplace((string) $word, '', $text);
        }

        $tokensIn = max(8, str_word_count($req->prompt));
        $tokensOut = max(12, str_word_count($text));

        return new AiResult($text, 0.92, $tokensIn, $tokensOut, 'mock');
    }

    public function classify(AiRequest $req): AiResult
    {
        $hay = strtolower($req->prompt.' '.json_encode($req->attributes));
        $bestId = null;
        $bestScore = 0.0;
        foreach ($req->categories as $cat) {
            $name = strtolower((string) ($cat['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $score = str_contains($hay, $name) ? 0.91 : 0.35;
            foreach (explode(' ', $name) as $token) {
                if ($token !== '' && str_contains($hay, $token)) {
                    $score = max($score, 0.82);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) $cat['id'];
            }
        }

        $label = $bestId ? 'Suggested category id '.$bestId : 'uncategorized';

        return new AiResult(
            text: $label,
            confidence: $bestScore,
            tokensIn: max(6, str_word_count($hay)),
            tokensOut: 4,
            provider: 'mock',
            suggestedCategoryId: $bestScore >= 0.8 ? $bestId : null,
        );
    }
}
