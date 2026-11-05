<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantApiKey extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_id', 'key_hash', 'revoked_at'];
    protected function casts(): array { return ['revoked_at' => 'datetime']; }
    public function merchant(): BelongsTo { return $this->belongsTo(Merchant::class); }
}
