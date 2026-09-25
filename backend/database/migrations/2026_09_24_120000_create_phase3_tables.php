<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->string('status', 32)->default('requested');
            $table->string('verification_token', 64);
            $table->timestamp('dns_verified_at')->nullable();
            $table->string('cert_status', 32)->default('none');
            $table->timestamp('last_check_at')->nullable();
            $table->unsignedTinyInteger('check_attempts')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 16);
            $table->string('token')->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('objective', 32)->default('product_visits');
            $table->string('status', 32)->default('draft');
            $table->decimal('daily_budget', 12, 2);
            $table->decimal('total_budget', 12, 2);
            $table->decimal('bid_cpc', 10, 4);
            $table->decimal('spent_today', 12, 2)->default(0);
            $table->decimal('spent_total', 12, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->index(['status', 'start_date', 'end_date']);
        });

        Schema::create('ad_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('match_type', 16)->default('exact');
            $table->timestamps();
            $table->unique(['campaign_id', 'product_id']);
        });

        Schema::create('ad_auctions', function (Blueprint $table) {
            $table->id();
            $table->string('slot_type', 24);
            $table->string('context_hash', 64);
            $table->foreignId('winner_campaign_id')->nullable()->constrained('ad_campaigns')->nullOnDelete();
            $table->foreignId('winner_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('winner_bid', 10, 4)->nullable();
            $table->decimal('runner_up_bid', 10, 4)->nullable();
            $table->decimal('charged_cpc', 10, 4)->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
        });

        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('auction_id')->nullable()->constrained('ad_auctions')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slot', 24);
            $table->string('user_hash', 64)->nullable();
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamps();
            $table->index(['campaign_id', 'created_at']);
        });

        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('impression_id')->nullable()->constrained('ad_impressions')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slot', 24);
            $table->string('user_hash', 64)->nullable();
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamps();
            $table->index(['campaign_id', 'user_hash', 'created_at']);
        });

        Schema::create('ad_spend_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->decimal('amount', 12, 4);
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix', 24);
            $table->string('key_hash', 64);
            $table->json('scopes');
            $table->string('environment', 8)->default('live');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index('key_prefix');
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('secret_hash', 64);
            $table->string('secret_prefix', 12);
            $table->json('event_types');
            $table->string('status', 16)->default('pending');
            $table->unsignedInteger('fail_count')->default(0);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event_id', 64);
            $table->string('event_type');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('status', 16)->default('pending');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestamps();
            $table->index(['endpoint_id', 'status']);
        });

        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id', 64)->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'created_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id', 64);
            $table->string('type');
            $table->string('user_hash', 64)->nullable();
            $table->json('payload');
            $table->timestamps();
            $table->index(['tenant_id', 'type', 'created_at']);
        });

        Schema::create('tenant_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('tone', 32)->default('warm');
            $table->string('length', 16)->default('medium');
            $table->json('banned_words')->nullable();
            $table->string('language', 8)->default('en');
            $table->unsignedInteger('monthly_token_budget')->default(50000);
            $table->boolean('opted_out')->default(false);
            $table->timestamps();
        });

        Schema::create('ai_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 32);
            $table->string('provider', 32);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status', 16)->default('ok');
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 32);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('prompt_hash', 64);
            $table->text('output');
            $table->string('review_status', 16)->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_categorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('suggested_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('confidence', 4, 3)->default(0);
            $table->boolean('accepted')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('insight_type', 32);
            $table->json('payload');
            $table->string('data_fingerprint', 64);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('ai_categorizations');
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('ai_usage');
        Schema::dropIfExists('tenant_ai_settings');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('domain_events');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('ad_spend_entries');
        Schema::dropIfExists('ad_clicks');
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('ad_auctions');
        Schema::dropIfExists('ad_targets');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_balances');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('tenant_domains');
    }
};
