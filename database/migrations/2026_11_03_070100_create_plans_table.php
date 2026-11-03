<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('base_price_paise');
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->unsignedBigInteger('included_units');
            $table->unsignedBigInteger('overage_rate_paise_per_unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Supports active-plan listings and plan selection per merchant.
            $table->index(['merchant_id', 'is_active']);
            // Supports merchant plan names and avoids duplicate catalog entries.
            $table->unique(['merchant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
