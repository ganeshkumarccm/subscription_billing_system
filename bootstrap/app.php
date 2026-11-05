<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'merchant.api_key' => \App\Http\Middleware\AuthenticateMerchantApiKey::class,
            'jwt.custom' => \App\Http\Middleware\AuthenticateJwt::class,
            'merchant.owner' => \App\Http\Middleware\EnsureMerchantOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The request could not be validated.',
                    'errors' => $exception->errors(),
                ], 422);
            }
        });
        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }
        });
        $exceptions->render(function (AccessDeniedHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage() ?: 'Forbidden.'], 403);
            }
        });
        $exceptions->render(function (TokenExpiredException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Authentication token has expired.'], 401);
            }
        });
        $exceptions->render(function (TokenInvalidException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Authentication token is invalid.'], 401);
            }
        });
    })->create();
