<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return response()->json(['message' => 'Authentication token is missing.'], 401);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException) {
            return response()->json(['message' => 'Authentication token has expired.'], 401);
        } catch (TokenInvalidException) {
            return response()->json(['message' => 'Authentication token is invalid.'], 401);
        } catch (\Throwable $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'expired')) {
                return response()->json(['message' => 'Authentication token has expired.'], 401);
            }

            return response()->json(['message' => 'Authentication token is invalid.'], 401);
        }

        if (! $user) {
            return response()->json(['message' => 'Authentication token is invalid.'], 401);
        }

        auth('api')->setUser($user);

        return $next($request);
    }
}
