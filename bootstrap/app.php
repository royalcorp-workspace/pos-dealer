<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SetLocalization::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            'v1.0/transfer-va/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax() || $request->is('login', 'api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi telah kedaluwarsa (CSRF token mismatch). Silakan coba masuk kembali.',
                    'csrf_expired' => true,
                    'csrf_token' => csrf_token(),
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('password', '_token'))
                ->with('error', 'Sesi Anda telah kedaluwarsa. Silakan coba masuk kembali.')
                ->with('show_login', true);
        });
    })->create();
