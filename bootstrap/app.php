<?php

use App\Adapters\Inbound\Http\Controllers\AuthController;
use App\Adapters\Inbound\Http\ErrorRenderer;
use App\Adapters\Inbound\Http\Middleware\JwtAuth;
use App\Adapters\Inbound\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            // H-02: /health is also served outside the prefix, as T-01 asks.
            Route::get('/health', [AuthController::class, 'health']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtAuth::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // RNF-4: a single error body for everything, with no stack traces.
        $exceptions->dontReport([\App\Domain\Exceptions\DomainError::class, \App\Adapters\Inbound\Http\AccessDenied::class]);
        $exceptions->render(fn (\Throwable $e) => ErrorRenderer::render($e));
    })->create();
