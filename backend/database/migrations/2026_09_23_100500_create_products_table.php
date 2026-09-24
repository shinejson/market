<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->decimal('price', 12, 2);
            $table->string('tax_class', 32)->nullable();
            $table->string('brand')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'store_id', 'slug']);
            $table->index(['tenant_id', 'status', 'category_id']);
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
