<?php

namespace App\Services\Ads;

use App\Models\AdAuction;
use App\Models\AdBalance;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdSpendEntry;
use App\Models\AdTarget;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class AdAuctionService
{
    public function auction(string $slot, ?string $query, ?int $categoryId, ?int $userId): ?array
    {
        $bypassed = TenantContext::isBypassed();
        TenantContext::bypass(true);
        try {
            $campaigns = AdCampaign::query()
                ->with(['targets.product.variants.inventory', 'store'])
                ->where('status', AdCampaign::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhereDate('start_date', '<=', now()->toDateString());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->get()
                ->filter(fn (AdCampaign $c) => $this->eligible($c));

            $candidates = [];
            foreach ($campaigns as $campaign) {
                foreach ($campaign->targets as $target) {
                    $product = $target->product;
                    if (! $product || $product->status !== Product::STATUS_ACTIVE) {
                        continue;
                    }
                    if ($categoryId && (int) $product->category_id !== $categoryId && $target->match_type !== 'exact') {
                        continue;
                    }
                    $ctr = $this->predictedCtr($product, $query);
                    $score = (float) $campaign->bid_cpc * $ctr;
                    $candidates[] = [
                        'campaign' => $campaign,
                        'product' => $product,
                        'bid' => (float) $campaign->bid_cpc,
                        'score' => $score,
                    ];
                }
            }

            usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);
            $winner = $candidates[0] ?? null;
            if (! $winner) {
                return null;
            }
            $runner = $candidates[1] ?? null;
            $floor = 0.05;
            $charged = max($floor, $runner ? ((float) $runner['bid'] + 0.01) : $floor);
            $charged = min($charged, (float) $winner['bid']);

            $auction = AdAuction::query()->create([
                'slot_type' => $slot,
                'context_hash' => hash('sha256', $slot.'|'.($query ?? '').'|'.($categoryId ?? '')),
                'winner_campaign_id' => $winner['campaign']->id,
                'winner_product_id' => $winner['product']->id,
                'winner_bid' => number_format($winner['bid'], 4, '.', ''),
                'runner_up_bid' => $runner ? number_format($runner['bid'], 4, '.', '') : null,
                'charged_cpc' => number_format($charged, 4, '.', ''),
                'decided_at' => now(),
            ]);

            $userHash = $userId ? hash('sha256', 'u:'.$userId) : null;
            $impression = AdImpression::query()->create([
                'campaign_id' => $winner['campaign']->id,
                'auction_id' => $auction->id,
                'product_id' => $winner['product']->id,
                'slot' => $slot,
                'user_hash' => $userHash,
                'cost' => '0.0000',
            ]);

            return [
                'auction_id' => $auction->id,
                'impression_id' => $impression->id,
                'campaign_id' => $winner['campaign']->id,
                'product_id' => $winner['product']->id,
                'slot' => $slot,
                'charged_cpc' => number_format($charged, 4, '.', ''),
                'product' => $winner['product'],
            ];
        } finally {
            TenantContext::bypass($bypassed);
        }
    }

    public function recordClick(int $impressionId, ?int $userId): bool
    {
        return DB::transaction(function () use ($impressionId, $userId) {
            $bypassed = TenantContext::isBypassed();
            TenantContext::bypass(true);
            try {
                $impression = AdImpression::query()->lockForUpdate()->find($impressionId);
                if (! $impression) {
                    return false;
                }
                $userHash = $userId ? hash('sha256', 'u:'.$userId) : $impression->user_hash;
                $recent = AdClick::query()
                    ->where('campaign_id', $impression->campaign_id)
                    ->where('user_hash', $userHash)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->exists();
                if ($recent) {
                    return false;
                }

                $auction = AdAuction::query()->find($impression->auction_id);
                $cost = (string) ($auction?->charged_cpc ?? '0.0500');
                $campaign = AdCampaign::query()->lockForUpdate()->find($impression->campaign_id);
                if (! $campaign) {
                    return false;
                }

                $balance = AdBalance::query()->where('tenant_id', $campaign->tenant_id)->lockForUpdate()->first();
                if (! $balance || bccomp((string) $balance->balance, $cost, 4) < 0) {
                    $campaign->update(['status' => AdCampaign::STATUS_EXHAUSTED]);

                    return false;
                }

                $balance->balance = bcsub((string) $balance->balance, $cost, 2);
                $balance->save();

                $campaign->spent_today = bcadd((string) $campaign->spent_today, $cost, 2);
                $campaign->spent_total = bcadd((string) $campaign->spent_total, $cost, 2);
                if (bccomp((string) $campaign->spent_total, (string) $campaign->total_budget, 2) >= 0
                    || bccomp((string) $campaign->spent_today, (string) $campaign->daily_budget, 2) >= 0) {
                    $campaign->status = AdCampaign::STATUS_EXHAUSTED;
                }
                $campaign->save();

                AdClick::query()->create([
                    'campaign_id' => $campaign->id,
                    'impression_id' => $impression->id,
                    'product_id' => $impression->product_id,
                    'slot' => $impression->slot,
                    'user_hash' => $userHash,
                    'cost' => $cost,
                ]);
                AdSpendEntry::query()->create([
                    'tenant_id' => $campaign->tenant_id,
                    'campaign_id' => $campaign->id,
                    'kind' => 'cpc',
                    'amount' => $cost,
                ]);

                return true;
            } finally {
                TenantContext::bypass($bypassed);
            }
        });
    }

    protected function eligible(AdCampaign $campaign): bool
    {
        if (bccomp((string) $campaign->spent_total, (string) $campaign->total_budget, 2) >= 0) {
            return false;
        }
        if (bccomp((string) $campaign->spent_today, (string) $campaign->daily_budget, 2) >= 0) {
            return false;
        }
        $balance = AdBalance::query()->where('tenant_id', $campaign->tenant_id)->first();
        if (! $balance || bccomp((string) $balance->balance, '0.05', 2) < 0) {
            return false;
        }
        $hasStock = $campaign->targets->contains(function (AdTarget $t) {
            $p = $t->product;
            if (! $p) {
                return false;
            }

            return $p->variants->contains(fn ($v) => $v->availableQty() > 0);
        });

        return $hasStock;
    }

    protected function predictedCtr(Product $product, ?string $query): float
    {
        $base = 0.08;
        if ($query) {
            $q = strtolower($query);
            if (str_contains(strtolower($product->name), $q) || str_contains(strtolower((string) $product->brand), $q)) {
                $base = 0.18;
            }
        }

        return $base;
    }
}
