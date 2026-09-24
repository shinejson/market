<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        TenantContext::clear();
    }

    public function test_customer_can_register_and_login(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Kojo',
            'email' => 'kojo@example.com',
            'password' => 'password123',
        ])->assertCreated()->assertJsonPath('data.user.role', 'customer');

        $this->postJson('/api/auth/login', [
            'email' => 'kojo@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.user.email', 'kojo@example.com');
    }

    public function test_marketplace_lists_active_products_publicly(): void
    {
        $this->getJson('/api/market/products')
            ->assertOk()
            ->assertJsonPath('meta.total', 8);
    }

    public function test_seller_cannot_see_other_tenant_products(): void
    {
        $seller1 = User::query()->where('email', 'seller1@markethub.test')->first();
        $seller2 = User::query()->where('email', 'seller2@markethub.test')->first();

        $other = $this->actingAs($seller2, 'sanctum')
            ->getJson('/api/tenant/products')
            ->assertOk()
            ->json('data');

        $this->actingAs($seller1, 'sanctum')
            ->getJson('/api/tenant/products')
            ->assertOk()
            ->assertJsonMissing(['id' => $other[0]['id']]);
    }

    public function test_checkout_splits_into_seller_orders_and_mock_payment(): void
    {
        $customer = User::query()->where('email', 'customer@markethub.test')->first();
        $address = Address::query()->where('user_id', $customer->id)->first();

        $north = Product::withoutGlobalScopes()->where('slug', 'pulse-wireless-headphones')->with('variants')->first();
        $kente = Product::withoutGlobalScopes()->where('slug', 'woven-market-tote')->with('variants')->first();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/cart/items', ['variant_id' => $north->variants->first()->id, 'qty' => 1])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/cart/items', ['variant_id' => $kente->variants->first()->id, 'qty' => 1])
            ->assertCreated();

        $checkout = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', ['shipping_address_id' => $address->id], [
                'Idempotency-Key' => 'test-key-1',
            ])
            ->assertCreated()
            ->json();

        $this->assertCount(2, $checkout['seller_orders']);
        $this->assertSame('pending_payment', $checkout['order']['status']);

        $this->getJson($checkout['payment']['url'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/orders/'.$checkout['order']['id'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_tenant_isolation_on_store_update(): void
    {
        $seller1 = User::query()->where('email', 'seller1@markethub.test')->first();
        $otherStore = Store::withoutGlobalScopes()
            ->where('slug', 'kente-home')
            ->first();

        $this->actingAs($seller1, 'sanctum')
            ->patchJson('/api/tenant/stores/'.$otherStore->id, ['name' => 'Hacked'])
            ->assertNotFound();
    }

    public function test_admin_can_approve_tenant(): void
    {
        $admin = User::query()->where('email', 'admin@markethub.test')->first();
        $tenant = Tenant::query()->first();
        $tenant->update(['status' => Tenant::STATUS_PENDING]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/tenants/'.$tenant->id, ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }
}
