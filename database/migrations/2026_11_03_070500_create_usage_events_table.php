<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 191);
            $table->unsignedBigInteger('units');
            $table->date('usage_date');
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Enforces idempotent ingestion per customer for atomic insert attempts.
            $table->unique(['customer_id', 'idempotency_key']);
            // Supports tenant usage dashboards over a date range.
            $table->index(['merchant_id', 'usage_date', 'customer_id']);
            // Supports subscription-period usage and invoice calculations.
            $table->index(['subscription_id', 'usage_date']);
            // Supports customer event drill-down ordered by occurrence.
            $table->index(['customer_id', 'usage_date', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
