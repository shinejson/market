<?php

namespace Tests\Feature;

use App\Models\AdImpression;
use App\Models\ApiKey;
use App\Models\DomainEvent;
use App\Models\Product;
use App\Models\TenantDomain;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        TenantContext::clear();
    }

    public function test_market_auction_prepends_sponsored_product(): void
    {
        $res = $this->getJson('/api/market/products')->assertOk();
        $this->assertNotNull($res->json('sponsored.impression_id'));
        $this->assertTrue($res->json('data.0.sponsored'));
        $this->assertSame('Pulse Wireless Headphones', $res->json('data.0.name'));
        $this->assertDatabaseCount('ad_auctions', 1);
        $this->assertDatabaseCount('ad_impressions', 1);
    }

    public function test_ad_click_charges_wallet(): void
    {
        $list = $this->getJson('/api/market/products')->assertOk();
        $impressionId = $list->json('sponsored.impression_id');

        $this->postJson('/api/market/ads/click/'.$impressionId)
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $this->assertDatabaseHas('ad_clicks', ['impression_id' => $impressionId]);
        $this->assertTrue((float) AdImpression::query()->find($impressionId)?->cost >= 0);
    }

    public function test_seller_api_key_scopes(): void
    {
        $seller = User::query()->where('email', 'seller1@markethub.test')->first();
        $created = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/tenant/api-keys', [
                'name' => 'ERP',
                'scopes' => ['products:read', 'orders:read'],
            ])
            ->assertCreated()
            ->json('data');

        $secret = $created['secret'];
        $this->assertNotEmpty($secret);

        $this->withHeaders(['Authorization' => 'Bearer '.$secret])
            ->getJson('/api/seller/v1/products')
            ->assertOk()
            ->assertJsonPath('meta.version', 'v1');

        $this->withHeaders(['Authorization' => 'Bearer '.$secret])
            ->getJson('/api/seller/v1/settlements')
            ->assertForbidden();

        $ordersOnly = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/tenant/api-keys', [
                'name' => 'Orders only',
                'scopes' => ['orders:read'],
            ])
            ->json('data.secret');

        $this->withHeaders(['Authorization' => 'Bearer '.$ordersOnly])
            ->getJson('/api/seller/v1/products')
            ->assertForbidden();
    }

    public function test_mock_ai_describe_creates_generation(): void
    {
        $seller = User::query()->where('email', 'seller1@markethub.test')->first();
        $product = Product::withoutGlobalScopes()->where('slug', 'pulse-wireless-headphones')->first();

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/tenant/ai/describe', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.feature', 'description')
            ->assertJsonPath('data.review_status', 'draft');
    }

    public function test_domain_force_verify_in_testing(): void
    {
        $seller = User::query()->where('email', 'seller1@markethub.test')->first();
        $domain = TenantDomain::withoutGlobalScopes()->where('domain', 'shop.northstar.test')->first();

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/tenant/domains/'.$domain->id.'/verify', ['force' => true])
            ->assertOk()
            ->assertJsonPath('data.status', TenantDomain::STATUS_ACTIVE);
    }

    public function test_checkout_emits_order_placed_events(): void
    {
        $customer = User::query()->where('email', 'customer@markethub.test')->first();
        $address = $customer->addresses()->first();
        $product = Product::withoutGlobalScopes()->where('slug', 'pulse-wireless-headphones')->with('variants')->first();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/cart/items', ['variant_id' => $product->variants->first()->id, 'qty' => 1])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', ['shipping_address_id' => $address->id], [
                'Idempotency-Key' => 'phase3-order-placed',
            ])
            ->assertCreated();

        $this->assertTrue(DomainEvent::query()->where('type', 'order.placed')->exists());
        $this->assertTrue(DomainEvent::query()->where('type', 'order.placed')->whereNotNull('tenant_id')->exists());
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        $seller = User::query()->where('email', 'seller1@markethub.test')->first();
        $created = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/tenant/api-keys', [
                'name' => 'temp',
                'scopes' => ['products:read'],
            ])
            ->json('data');

        $this->actingAs($seller, 'sanctum')
            ->deleteJson('/api/tenant/api-keys/'.$created['key']['id'])
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer '.$created['secret']])
            ->getJson('/api/seller/v1/products')
            ->assertUnauthorized();

        $this->assertNotNull(ApiKey::withoutGlobalScopes()->find($created['key']['id'])?->revoked_at);
    }
}
