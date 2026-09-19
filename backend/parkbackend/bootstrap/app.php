<?php

use App\Http\Middleware\Role;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__ . '/../routes/web.php', api: __DIR__ . '/../routes/api.php', commands: __DIR__ . '/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => Role::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn(Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (PDOException $e, Request $request) {
            if ($request->is('api/*') && ($e->errorInfo[0] ?? '') === '45000') {
                return response()->json(['message' => $e->errorInfo[2] ?? 'Operation rejected'], 422);
            }
        });
    })->create();
