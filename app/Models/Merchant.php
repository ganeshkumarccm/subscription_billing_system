<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function apiKeys(): HasMany { return $this->hasMany(MerchantApiKey::class); }
    public function plans(): HasMany { return $this->hasMany(Plan::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function usageEvents(): HasMany { return $this->hasMany(UsageEvent::class); }
}
