<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamps();

            // Supports the dashboard's active-subscription list by tenant.
            $table->index(['merchant_id', 'status', 'current_period_end']);
            // Supports customer subscription history and current-period lookup.
            $table->index(['customer_id', 'status', 'current_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
