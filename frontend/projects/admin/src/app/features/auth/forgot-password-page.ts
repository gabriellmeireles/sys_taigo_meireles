import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { UiButton, UiFormField, UiInput, UiLogo, UiState } from '@tm/ui';
import { errorMessage } from '../../core/api/errors';
import { AuthService } from '../../core/auth/auth.service';

/** Tela 4.2 — Esqueci minha senha (resposta igual exista ou não o e-mail). */
@Component({
  selector: 'adm-forgot-password-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, UiButton, UiFormField, UiInput, UiLogo, UiState],
  template: `
    <main class="adm-auth">
      <div class="adm-auth__card">
        <ui-logo variant="selo" label="Taigo Meireles" />
        @if (sent()) {
          <ui-state kind="success" title="Verifique seu e-mail" [message]="sent()" [focusable]="true">
            <a routerLink="/entrar" uiButton="secondary">Voltar ao login</a>
          </ui-state>
        } @else {
          <h1>Esqueci minha senha</h1>
          <p class="ui-body-sm ui-muted">Informe o e-mail de acesso. Enviaremos um link para criar uma nova senha.</p>
          <form class="ui-stack" style="--stack-gap: var(--space-5)" [formGroup]="form" (ngSubmit)="submit()" novalidate>
            @if (error()) {
              <p class="adm-notice adm-notice--error" role="alert">{{ error() }}</p>
            }
            <ui-form-field label="E-mail" [error]="emailError()">
              <input uiInput type="email" formControlName="email" autocomplete="username" />
            </ui-form-field>
            <button uiButton="primary" type="submit" [block]="true" [loading]="loading()">
              {{ loading() ? 'Enviando…' : 'Enviar link' }}
            </button>
            <a routerLink="/entrar" class="ui-btn ui-btn--link" style="align-self:center">Voltar ao login</a>
          </form>
        }
      </div>
    </main>
  `,
})
export class ForgotPasswordPage {
  private readonly auth = inject(AuthService);
  protected readonly form = inject(FormBuilder).nonNullable.group({ email: ['', [Validators.required, Validators.email]] });
  protected readonly loading = signal(false);
  protected readonly sent = signal<string | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly emailError = signal<string | null>(null);

  protected submit(): void {
    this.emailError.set(this.form.invalid ? 'Informe um e-mail válido.' : null);
    if (this.form.invalid) return;
    this.loading.set(true);
    this.error.set(null);
    this.auth.forgotPassword(this.form.getRawValue().email).subscribe({
      next: (r) => this.sent.set(r.message),
      error: (err) => {
        this.loading.set(false);
        this.error.set(errorMessage(err));
      },
    });
  }
}
