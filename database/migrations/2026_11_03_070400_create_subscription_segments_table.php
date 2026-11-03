<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            // Supports day-range segment lookup while calculating invoices.
            $table->index(['subscription_id', 'starts_at', 'ends_at']);
            // Supports plan usage and revenue reporting by segment period.
            $table->index(['plan_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_segments');
    }
};
