<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_daily_aggregates', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedBigInteger('units_total')->default(0);
            $table->unsignedBigInteger('event_count')->default(0);
            $table->timestamps();

            $table->primary(['customer_id', 'usage_date']);
            // Supports merchant dashboards after joining through customers.
            $table->index(['usage_date', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_daily_aggregates');
    }
};
