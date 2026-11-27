<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Login por sessão com cookie (Sanctum, modo SPA).
 * Antes do login, o painel chama GET /sanctum/csrf-cookie.
 */
class SessionController extends Controller
{
    public function store(LoginRequest $request): AdminResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        $admin = $request->user();
        $admin->forceFill(['last_login_at' => now()])->save();

        return new AdminResource($admin);
    }

    public function show(Request $request): AdminResource
    {
        return new AdminResource($request->user());
    }

    /** Minha conta: nome e e-mail. */
    public function update(Request $request): AdminResource
    {
        $admin = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore($admin->id)],
        ]);

        if (isset($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        $admin->update($data);

        return new AdminResource($admin);
    }

    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
