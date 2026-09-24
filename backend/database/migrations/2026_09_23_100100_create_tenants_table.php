<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('pending');
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('business_name')->nullable();
            $table->text('business_details')->nullable();
            $table->unsignedBigInteger('payout_details_id')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
