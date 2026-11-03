<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MerchantContextService
{
    public function fromRoute(Request $request): Merchant
    {
        $merchantId = (int) $request->route('merchant');
        $user = auth('api')->user();

        if (! $user || $user->merchant_id !== $merchantId) {
            throw new AccessDeniedHttpException('You do not own this merchant.');
        }

        return Merchant::query()->findOrFail($merchantId);
    }

    public function fromUser(): Merchant
    {
        return auth('api')->user()->merchant;
    }
}
