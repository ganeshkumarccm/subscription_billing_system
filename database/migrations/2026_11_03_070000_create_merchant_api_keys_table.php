<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('key_hash', 64)->unique();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Supports active-key lookup and merchant key administration.
            $table->index(['merchant_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_api_keys');
    }
};
