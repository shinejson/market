<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\TenantAiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AIGateway
{
    public function __construct(
        protected AiProvider $provider,
    ) {}

    public function complete(int $tenantId, AiRequest $req): AiResult
    {
        return $this->run($tenantId, $req, fn () => $this->provider->complete($req));
    }

    public function classify(int $tenantId, AiRequest $req): AiResult
    {
        return $this->run($tenantId, $req, fn () => $this->provider->classify($req));
    }

    protected function run(int $tenantId, AiRequest $req, callable $fn): AiResult
    {
        $settings = TenantAiSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['tone' => 'warm', 'length' => 'medium', 'language' => 'en', 'monthly_token_budget' => 50000],
        );

        if ($settings->opted_out) {
            throw ValidationException::withMessages(['ai' => 'AI features are disabled for this tenant.']);
        }

        $used = (int) AiUsage::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum(\Illuminate\Support\Facades\DB::raw('tokens_in + tokens_out'));

        if ($used >= (int) $settings->monthly_token_budget) {
            throw ValidationException::withMessages(['ai' => 'Monthly AI token budget exhausted.']);
        }

        $req->tone ??= $settings->tone;
        $req->length ??= $settings->length;
        $req->bannedWords = array_merge($req->bannedWords, $settings->banned_words ?? []);
        $req->prompt = $this->scrub($req->prompt);

        $cacheKey = 'ai:'.$tenantId.':'.hash('sha256', $req->feature.'|'.$req->prompt.'|'.json_encode($req->attributes));
        $cached = Cache::get($cacheKey);
        if ($cached instanceof AiResult) {
            return $cached;
        }

        $start = microtime(true);
        $result = $fn();
        $latency = (int) ((microtime(true) - $start) * 1000);

        AiUsage::query()->create([
            'tenant_id' => $tenantId,
            'feature' => $req->feature,
            'provider' => $result->provider,
            'tokens_in' => $result->tokensIn,
            'tokens_out' => $result->tokensOut,
            'cost_estimate' => number_format(($result->tokensIn + $result->tokensOut) * 0.000002, 6, '.', ''),
            'latency_ms' => $latency,
            'status' => 'ok',
        ]);

        Cache::put($cacheKey, $result, now()->addHours(12));

        return $result;
    }

    protected function scrub(string $text): string
    {
        $text = preg_replace('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', '[redacted-email]', $text) ?? $text;
        $text = preg_replace('/\b\+?\d[\d\s\-()]{7,}\b/', '[redacted-phone]', $text) ?? $text;

        return $text;
    }
}
