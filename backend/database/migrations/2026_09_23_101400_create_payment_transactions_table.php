<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 64);
            $table->string('gateway_ref')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->default('initiated');
            $table->string('idempotency_key')->nullable();
            $table->string('raw_webhook_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_ref']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
