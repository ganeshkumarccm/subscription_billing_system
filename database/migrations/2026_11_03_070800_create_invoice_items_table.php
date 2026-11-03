<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['base', 'overage']);
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('amount');
            $table->date('segment_start');
            $table->date('segment_end');
            $table->timestamps();

            // Supports invoice rendering and itemized invoice history.
            $table->index(['invoice_id', 'type']);
            // Supports revenue reporting by plan and item type.
            $table->index(['plan_id', 'type', 'segment_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
