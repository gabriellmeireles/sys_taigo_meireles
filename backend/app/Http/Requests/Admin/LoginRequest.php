<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Confere e-mail e senha de um administrador ativo, com limite de tentativas.
     * A mensagem de erro é sempre a mesma, para não revelar se o e-mail existe.
     */
    public function authenticate(): void
    {
        $key = $this->throttleKey();
        $max = (int) config('platform.login_max_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$minutes} minuto(s).",
            ])->status(429);
        }

        $ok = Auth::guard('web')->attempt([
            'email' => $this->string('email')->lower()->toString(),
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ]);

        if (! $ok) {
            RateLimiter::hit($key, (int) config('platform.login_decay_seconds'));

            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($key);
    }

    private function throttleKey(): string
    {
        return 'admin-login:'.Str::lower($this->string('email')->toString()).'|'.$this->ip();
    }
}
