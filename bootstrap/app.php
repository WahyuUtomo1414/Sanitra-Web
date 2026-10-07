<?php

use App\Models\ErrorLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->reportable(function (Throwable $e): bool {
            $skip = [
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
                ModelNotFoundException::class,
                NotFoundHttpException::class,
            ];

            foreach ($skip as $skipped) {
                if ($e instanceof $skipped) {
                    return false;
                }
            }

            try {
                ErrorLog::create([
                    'level' => 'error',
                    'message' => $e->getMessage(),
                    'created_at' => now(),
                    'url' => request()->fullUrl() ?: null,
                    'method' => request()->method() ?: null,
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip() ?: null,
                    'trace' => collect($e->getTrace())
                        ->take(10)
                        ->map(fn (array $frame): array => [
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'call' => ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''),
                        ])
                        ->all(),
                    'context' => [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'http_code' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : null,
                    ],
                ]);
            } catch (Throwable) {
                // Jangan throw — kegagalan logging tidak boleh menutupi error asli.
            }

            return true;
        });
    })->create();
