<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'merchant_id', 'status', 'started_at', 'current_period_start', 'current_period_end'];
    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'current_period_start' => 'datetime', 'current_period_end' => 'datetime'];
    }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function segments(): HasMany { return $this->hasMany(SubscriptionSegment::class); }
    public function usageEvents(): HasMany { return $this->hasMany(UsageEvent::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
}
