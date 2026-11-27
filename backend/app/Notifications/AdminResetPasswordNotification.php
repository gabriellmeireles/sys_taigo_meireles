<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** E-mail de "esqueci minha senha" do painel. */
class AdminResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function url(object $notifiable): string
    {
        return config('platform.admin_url').'/redefinir-senha?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.admins.expire');

        return (new MailMessage)
            ->subject('Redefinição de senha — Painel Taigo Meireles')
            ->greeting('Olá, '.$notifiable->name.'.')
            ->line('Recebemos um pedido para redefinir a senha do seu acesso ao painel.')
            ->action('Definir nova senha', $this->url($notifiable))
            ->line("Este link vale por {$minutes} minutos e só pode ser usado uma vez.")
            ->line('Se você não fez este pedido, ignore este e-mail. Sua senha continua a mesma.')
            ->salutation('Painel Taigo Meireles');
    }
}
