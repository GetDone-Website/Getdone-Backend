<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API uses Sanctum Bearer tokens, not cross-site cookies.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) return null;
            // Delegate to Laravel for correct HTTP code, never return a stack trace to browser.
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : ($e instanceof \Illuminate\Auth\AuthenticationException ? 401 : 500);
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) $status = 404;
            if ($status === 422) return null;
            $message = match ($status) {
                401 => 'Sesi tidak valid. Silakan masuk kembali.',
                403 => 'Anda tidak memiliki akses.',
                404 => 'Data tidak ditemukan.',
                405 => 'Metode tidak diizinkan.',
                429 => 'Terlalu banyak permintaan. Coba lagi sebentar.',
                500 => 'Terjadi kesalahan server.',
                default => ($status < 500 ? 'Permintaan gagal.' : 'Server sedang bermasalah.'),
            };
            return response()->json(['success' => false, 'message' => $message], $status);
        });
    })->create();
