<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
