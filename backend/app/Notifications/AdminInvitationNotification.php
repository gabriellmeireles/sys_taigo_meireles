<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Convite para um novo administrador definir a senha e entrar no painel. */
class AdminInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token, public ?string $invitedBy = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function url(object $notifiable): string
    {
        return config('platform.admin_url').'/definir-senha?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = intdiv((int) config('auth.passwords.invites.expire'), 60);
        $by = $this->invitedBy ? " por {$this->invitedBy}" : '';

        return (new MailMessage)
            ->subject('Convite para o Painel Taigo Meireles')
            ->greeting('Olá, '.$notifiable->name.'.')
            ->line("Você foi convidado{$by} para administrar o site de Taigo Meireles.")
            ->action('Definir minha senha', $this->url($notifiable))
            ->line("Este link vale por {$hours} horas. Depois disso, peça um novo convite.")
            ->salutation('Painel Taigo Meireles');
    }
}
