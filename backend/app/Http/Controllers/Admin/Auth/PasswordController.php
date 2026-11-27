<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Recuperação de senha, aceite de convite e troca da própria senha.
 * Tokens gerados, guardados com hash e expirados pelo Password Broker do Laravel.
 */
class PasswordController extends Controller
{
    /** Sempre a mesma resposta, exista ou não o e-mail. */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('admins')->sendResetLink([
            'email' => mb_strtolower($request->string('email')->toString()),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        return $this->setPasswordWithToken($request, 'admins', 'Senha alterada. Entre com a nova senha.');
    }

    public function acceptInvite(Request $request): JsonResponse
    {
        return $this->setPasswordWithToken($request, 'invites', 'Senha definida. Entre com o seu e-mail e a nova senha.');
    }

    /** Troca a própria senha (logado) e encerra as outras sessões. */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', PasswordRule::min(config('platform.password_min_length'))],
        ], [
            'current_password.current_password' => 'A senha atual está incorreta.',
        ]);

        $admin = $request->user();
        $admin->forceFill([
            'password_hash' => $request->string('password')->toString(),
            'password_changed_at' => now(),
        ])->save();

        $admin->endSessions(exceptSessionId: $request->session()->getId());
        $request->session()->regenerate();

        return response()->json(['message' => 'Senha alterada.']);
    }

    private function setPasswordWithToken(Request $request, string $broker, string $successMessage): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(config('platform.password_min_length'))],
        ]);

        $status = Password::broker($broker)->reset(
            [
                'email' => mb_strtolower($request->string('email')->toString()),
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password_confirmation')->toString(),
                'token' => $request->string('token')->toString(),
                'is_active' => true,
            ],
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password_hash' => $password,
                    'password_changed_at' => now(),
                ])->save();

                // Quem redefine a senha encerra todas as sessões abertas.
                $admin->endSessions();

                event(new PasswordReset($admin));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Este link é inválido ou expirou. Solicite um novo.',
            ]);
        }

        return response()->json(['message' => $successMessage]);
    }
}
