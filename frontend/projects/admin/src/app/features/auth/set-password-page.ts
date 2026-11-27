import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { UiButton, UiFormField, UiInput, UiLogo, UiState } from '@tm/ui';
import { errorMessage, fieldErrors } from '../../core/api/errors';
import { AuthService } from '../../core/auth/auth.service';

export const MIN_PASSWORD = 12;

/**
 * Tela 4.2 — Redefinir senha (link do e-mail) e definir a primeira senha (convite).
 * Rotas: /redefinir-senha?token&email (mode=reset) e /definir-senha?token&email (mode=invite).
 */
@Component({
  selector: 'adm-set-password-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, UiButton, UiFormField, UiInput, UiLogo, UiState],
  template: `
    <main class="adm-auth">
      <div class="adm-auth__card">
        <ui-logo variant="selo" label="Taigo Meireles" />
        @if (done()) {
          <ui-state kind="success" [title]="isInvite() ? 'Senha definida' : 'Senha alterada'" [message]="done()" [focusable]="true">
            <a routerLink="/entrar" uiButton="primary">Entrar</a>
          </ui-state>
        } @else if (!token() || !email()) {
          <ui-state kind="error" title="Link incompleto" message="Abra o link exatamente como chegou no e-mail ou peça um novo.">
            <a routerLink="/esqueci-a-senha" uiButton="secondary">Pedir novo link</a>
          </ui-state>
        } @else {
          <h1>{{ isInvite() ? 'Definir minha senha' : 'Criar nova senha' }}</h1>
          <p class="ui-body-sm ui-muted">Acesso de {{ email() }}. Use pelo menos {{ min }} caracteres.</p>
          <form class="ui-stack" style="--stack-gap: var(--space-5)" [formGroup]="form" (ngSubmit)="submit()" novalidate>
            @if (error()) {
              <div class="adm-notice adm-notice--error" role="alert">
                {{ error() }}
                @if (expired()) {
                  <a routerLink="/esqueci-a-senha">Pedir novo link</a>
                }
              </div>
            }
            <ui-form-field label="Nova senha" [hint]="'Mínimo de ' + min + ' caracteres.'" [error]="passwordError()">
              <input uiInput type="password" formControlName="password" autocomplete="new-password" />
            </ui-form-field>
            <ui-form-field label="Confirme a nova senha" [error]="confirmError()">
              <input uiInput type="password" formControlName="password_confirmation" autocomplete="new-password" />
            </ui-form-field>
            <button uiButton="primary" type="submit" [block]="true" [loading]="loading()">
              {{ loading() ? 'Salvando…' : 'Salvar senha' }}
            </button>
          </form>
        }
      </div>
    </main>
  `,
})
export class SetPasswordPage {
  private readonly auth = inject(AuthService);

  readonly mode = input<'reset' | 'invite'>('reset'); // data da rota
  readonly token = input<string | undefined>();
  readonly email = input<string | undefined>();

  protected readonly min = MIN_PASSWORD;
  protected readonly isInvite = computed(() => this.mode() === 'invite');
  protected readonly form = inject(FormBuilder).nonNullable.group({ password: [''], password_confirmation: [''] });
  protected readonly loading = signal(false);
  protected readonly done = signal<string | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly expired = signal(false);
  protected readonly passwordError = signal<string | null>(null);
  protected readonly confirmError = signal<string | null>(null);

  protected submit(): void {
    const { password, password_confirmation } = this.form.getRawValue();
    this.passwordError.set(password.length < MIN_PASSWORD ? `A senha precisa ter pelo menos ${MIN_PASSWORD} caracteres.` : null);
    this.confirmError.set(password !== password_confirmation ? 'As senhas não são iguais.' : null);
    if (this.passwordError() || this.confirmError()) return;

    this.loading.set(true);
    this.error.set(null);
    this.auth
      .setPassword(this.mode(), { token: this.token()!, email: this.email()!, password, password_confirmation })
      .subscribe({
        next: (r) => this.done.set(r.message),
        error: (err) => {
          this.loading.set(false);
          const fields = fieldErrors(err);
          this.expired.set(!!fields['token']);
          this.passwordError.set(fields['password']?.[0] ?? null);
          this.error.set(fields['token']?.[0] ?? (fields['password'] ? null : errorMessage(err)));
        },
      });
  }
}
