<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['subscription_id', 'period_start', 'period_end', 'subtotal', 'status'];
    protected function casts(): array { return ['period_start' => 'date', 'period_end' => 'date', 'subtotal' => 'integer']; }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
}
