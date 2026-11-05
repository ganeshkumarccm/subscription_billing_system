<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id', 'name', 'base_price_paise', 'billing_cycle', 'included_units', 'overage_rate_paise_per_unit', 'is_active'];
    protected function casts(): array
    {
        return ['base_price_paise' => 'integer', 'included_units' => 'integer', 'overage_rate_paise_per_unit' => 'integer', 'is_active' => 'boolean'];
    }
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
    public function subscriptionSegments(): HasMany { return $this->hasMany(SubscriptionSegment::class); }
    public function invoiceItems(): HasMany { return $this->hasMany(InvoiceItem::class); }
}
