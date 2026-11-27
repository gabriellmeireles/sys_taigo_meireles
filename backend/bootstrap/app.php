<?php

use App\Http\Middleware\EnsureAdminIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        // API versionada: /api/v1/public/... e /api/v1/admin/... (docs/05-arquitetura.md §6.3)
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sessão com cookie para requisições vindas do site e do painel (Sanctum, modo SPA).
        $middleware->statefulApi();

        // API não redireciona para tela de login: responde 401 em JSON.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');

        $middleware->alias([
            'admin.active' => EnsureAdminIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
