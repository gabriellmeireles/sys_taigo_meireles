<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ArtworkController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\Admin\TechniqueController;
use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Admin\Auth\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — prefixo /api/v1 (bootstrap/app.php)
|--------------------------------------------------------------------------
| /api/v1/public/...  site público (sem login) — próximas etapas
| /api/v1/admin/...   painel (login por sessão, Sanctum modo SPA)
| Organização por domínio: docs/05-arquitetura.md §6.3
*/

Route::prefix('admin')->group(function () {

    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::post('login', [SessionController::class, 'store']);
        Route::post('forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:6,1');
        Route::post('reset-password', [PasswordController::class, 'reset'])->middleware('throttle:6,1');
        Route::post('accept-invite', [PasswordController::class, 'acceptInvite'])->middleware('throttle:6,1');

        Route::middleware(['auth:sanctum', 'admin.active'])->group(function () {
            Route::get('me', [SessionController::class, 'show']);
            Route::patch('me', [SessionController::class, 'update']);
            Route::put('password', [PasswordController::class, 'update']);
            Route::post('logout', [SessionController::class, 'destroy']);
        });
    });

    // Área autenticada do painel
    Route::middleware(['auth:sanctum', 'admin.active'])->group(function () {
        Route::apiResource('admins', AdminController::class)->except('show');
        Route::post('admins/{admin}/resend-invite', [AdminController::class, 'resendInvite']);

        // Catálogo
        foreach (['categories' => CategoryController::class, 'techniques' => TechniqueController::class, 'series' => SeriesController::class] as $uri => $controller) {
            Route::put("{$uri}/order", [$controller, 'reorder']);
            Route::get($uri, [$controller, 'index']);
            Route::post($uri, [$controller, 'store']);
            Route::put("{$uri}/{id}", [$controller, 'update'])->whereNumber('id');
            Route::delete("{$uri}/{id}", [$controller, 'destroy'])->whereNumber('id');
        }

        Route::apiResource('artworks', ArtworkController::class);
        Route::patch('artworks/{artwork}/status', [ArtworkController::class, 'updateStatus']);

        // Mídia (imagens)
        Route::post('media', [MediaController::class, 'store']);
        Route::get('media/{media}', [MediaController::class, 'show']);
        Route::get('media/{media}/original', [MediaController::class, 'original']);
    });
});
