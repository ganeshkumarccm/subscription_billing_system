<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageDailyAggregate extends Model
{
    use HasFactory;

    protected $table = 'usage_daily_aggregates';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $fillable = ['customer_id', 'usage_date', 'units_total', 'event_count'];
    protected function casts(): array { return ['usage_date' => 'date', 'units_total' => 'integer', 'event_count' => 'integer']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
