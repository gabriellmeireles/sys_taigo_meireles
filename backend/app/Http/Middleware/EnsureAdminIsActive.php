<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encerra a sessão de um administrador que foi desativado enquanto estava logado.
 */
class EnsureAdminIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if ($admin && ! $admin->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Sua conta foi desativada. Fale com outro administrador.'], 401);
        }

        return $next($request);
    }
}
