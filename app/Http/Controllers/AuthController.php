<?php

namespace App\Http\Controllers;

use App\Actions\AttemptLoginAction;
use App\Actions\RegisterMerchantAction;
use App\DTOs\AttemptLoginData;
use App\DTOs\RegisterMerchantData;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterMerchantRequest;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(RegisterMerchantRequest $request, RegisterMerchantAction $action): JsonResponse
    {
        $user = $action->execute(new RegisterMerchantData(
            $request->string('merchant_name')->toString(),
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        ));

        return $this->tokenResponse(auth('api')->login($user), 201);
    }

    public function login(LoginRequest $request, AttemptLoginAction $action): JsonResponse
    {
        try {
            $user = $action->execute(new AttemptLoginData(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            ));
        } catch (InvalidCredentialsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        return $this->tokenResponse(auth('api')->login($user));
    }

    public function me(): JsonResponse
    {
        return response()->json(['data' => auth('api')->user()->load('merchant')]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out.']);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(JWTAuth::refresh(JWTAuth::getToken()));
    }

    private function tokenResponse(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], $status);
    }
}
