<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\AdBalance;
use App\Models\AdCampaign;
use App\Models\AdTarget;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\TenantAiSetting;
use App\Models\TenantDomain;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'admin@markethub.test',
            'password' => Hash::make('password'),
            'phone' => '+10000000000',
            'email_verified_at' => now(),
        ]);
        UserRole::query()->create(['user_id' => $admin->id, 'role' => 'super_admin']);

        $customer = User::query()->create([
            'name' => 'Ama Mensah',
            'email' => 'customer@markethub.test',
            'password' => Hash::make('password'),
            'phone' => '+233201111111',
            'email_verified_at' => now(),
        ]);
        UserRole::query()->create(['user_id' => $customer->id, 'role' => 'customer']);
        Address::query()->create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'full_name' => 'Ama Mensah',
            'phone' => '+233201111111',
            'line1' => '14 Independence Ave',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'postal_code' => 'GA-000',
            'country' => 'GH',
            'is_default' => true,
        ]);

        $this->seedSeller(
            email: 'seller1@markethub.test',
            tenantName: 'Northstar Gadgets',
            storeName: 'Northstar Electronics',
            storeSlug: 'northstar',
            country: 'GH',
            products: [
                ['name' => 'Pulse Wireless Headphones', 'price' => 89.00, 'qty' => 40, 'cat' => 'Audio', 'brand' => 'Pulse'],
                ['name' => 'Orbit Smartwatch', 'price' => 149.00, 'qty' => 25, 'cat' => 'Wearables', 'brand' => 'Orbit'],
                ['name' => 'Nimbus Bluetooth Speaker', 'price' => 59.00, 'qty' => 8, 'cat' => 'Audio', 'brand' => 'Nimbus'],
                ['name' => 'Aero USB-C Hub', 'price' => 39.00, 'qty' => 60, 'cat' => 'Accessories', 'brand' => 'Aero'],
            ],
        );

        $this->seedSeller(
            email: 'seller2@markethub.test',
            tenantName: 'Kente & Co',
            storeName: 'Kente Home',
            storeSlug: 'kente-home',
            country: 'GH',
            products: [
                ['name' => 'Handwoven Throw Blanket', 'price' => 72.00, 'qty' => 18, 'cat' => 'Home', 'brand' => 'Kente'],
                ['name' => 'Ceramic Pour-Over Set', 'price' => 48.00, 'qty' => 22, 'cat' => 'Kitchen', 'brand' => 'Clayhouse'],
                ['name' => 'Shea Body Butter 200ml', 'price' => 16.00, 'qty' => 80, 'cat' => 'Beauty', 'brand' => 'SheaGold'],
                ['name' => 'Woven Market Tote', 'price' => 28.00, 'qty' => 4, 'cat' => 'Home', 'brand' => 'Kente'],
            ],
        );

        $this->seedPhase3();
    }

    protected function seedPhase3(): void
    {
        $north = Tenant::query()->where('slug', 'northstar-gadgets')->first();
        $store = Store::withoutGlobalScopes()->where('slug', 'northstar')->first();
        $product = Product::withoutGlobalScopes()->where('slug', 'pulse-wireless-headphones')->first();
        if (! $north || ! $store || ! $product) {
            return;
        }

        AdBalance::query()->firstOrCreate(
            ['tenant_id' => $north->id],
            ['balance' => 50.00],
        );
        $campaign = AdCampaign::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $north->id, 'name' => 'Pulse launch'],
            [
                'store_id' => $store->id,
                'status' => AdCampaign::STATUS_ACTIVE,
                'daily_budget' => 20,
                'total_budget' => 200,
                'bid_cpc' => 0.35,
                'start_date' => now()->toDateString(),
            ],
        );
        AdTarget::query()->firstOrCreate([
            'campaign_id' => $campaign->id,
            'product_id' => $product->id,
        ], ['match_type' => 'exact']);
        TenantAiSetting::query()->firstOrCreate(
            ['tenant_id' => $north->id],
            ['tone' => 'warm', 'length' => 'medium', 'language' => 'en', 'monthly_token_budget' => 50000],
        );
        TenantDomain::withoutGlobalScopes()->firstOrCreate(
            ['domain' => 'shop.northstar.test'],
            [
                'tenant_id' => $north->id,
                'status' => TenantDomain::STATUS_DNS_PENDING,
                'verification_token' => 'demo-verify-token-northstar',
                'cert_status' => 'none',
            ],
        );
    }

    protected function seedSeller(string $email, string $tenantName, string $storeName, string $storeSlug, string $country, array $products): void
    {
        $owner = User::query()->create([
            'name' => $tenantName.' Owner',
            'email' => $email,
            'password' => Hash::make('password'),
            'phone' => '+233200000000',
            'email_verified_at' => now(),
        ]);

        $tenant = Tenant::query()->create([
            'name' => $tenantName,
            'slug' => Str::slug($tenantName),
            'status' => Tenant::STATUS_ACTIVE,
            'owner_user_id' => $owner->id,
            'country' => $country,
            'business_name' => $tenantName,
        ]);

        UserRole::query()->create([
            'user_id' => $owner->id,
            'role' => 'tenant_owner',
            'tenant_id' => $tenant->id,
        ]);

        $store = Store::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => $storeName,
            'slug' => $storeSlug,
            'status' => Store::STATUS_ACTIVE,
            'currency' => 'USD',
            'delivery_fee' => 5.00,
            'delivery_days' => 4,
            'description' => $storeName.' — curated goods for everyday life.',
            'contact_email' => $email,
            'city' => 'Accra',
            'country' => $country,
            'is_featured' => true,
        ]);

        $cats = [];
        foreach (collect($products)->pluck('cat')->unique() as $catName) {
            $cats[$catName] = Category::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => $catName,
                'slug' => Str::slug($catName),
                'position' => count($cats),
            ]);
        }

        foreach ($products as $p) {
            $product = Product::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'store_id' => $store->id,
                'category_id' => $cats[$p['cat']]->id,
                'name' => $p['name'],
                'slug' => Str::slug($p['name']),
                'description' => $p['name'].' from '.$storeName.'. Quality marketplace listing with tracked inventory.',
                'status' => Product::STATUS_ACTIVE,
                'price' => $p['price'],
                'brand' => $p['brand'],
                'has_variants' => false,
                'is_featured' => true,
            ]);

            $variant = ProductVariant::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'sku' => strtoupper(Str::slug($p['name'], '-')).'-001',
                'options' => ['default' => 'standard'],
                'status' => ProductVariant::STATUS_ACTIVE,
            ]);

            Inventory::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'variant_id' => $variant->id,
                'quantity' => $p['qty'],
                'reserved' => 0,
                'low_stock_threshold' => 5,
            ]);

            ProductImage::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'path' => 'placeholders/'.$product->slug.'.svg',
                'position' => 0,
                'is_primary' => true,
            ]);
        }
    }
}
