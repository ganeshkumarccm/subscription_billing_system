<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id', 'customer_id', 'subscription_id', 'idempotency_key', 'units', 'usage_date', 'occurred_at'];
    protected function casts(): array { return ['units' => 'integer', 'usage_date' => 'date', 'occurred_at' => 'datetime']; }
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
