<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'type', 'plan_id', 'description', 'quantity', 'unit_price', 'amount', 'segment_start', 'segment_end'];
    protected function casts(): array { return ['quantity' => 'integer', 'unit_price' => 'integer', 'amount' => 'integer', 'segment_start' => 'date', 'segment_end' => 'date']; }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
}
