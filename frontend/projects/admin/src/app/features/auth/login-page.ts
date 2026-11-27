import { ChangeDetectionStrategy, Component, ElementRef, inject, input, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { UiButton, UiFormField, UiInput, UiLogo } from '@tm/ui';
import { errorMessage, fieldErrors } from '../../core/api/errors';
import { AuthService } from '../../core/auth/auth.service';

/** Tela 4.1 — Login por e-mail e senha. */
@Component({
  selector: 'adm-login-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, UiButton, UiFormField, UiInput, UiLogo],
  template: `
    <main class="adm-auth">
      <form class="adm-auth__card" [formGroup]="form" (ngSubmit)="submit()" novalidate>
        <ui-logo variant="selo" label="Taigo Meireles" />
        <h1>Entrar no painel</h1>

        @if (expirada()) {
          <p class="adm-notice">Sua sessão expirou. Entre novamente para continuar.</p>
        }
        @if (error()) {
          <p class="adm-notice adm-notice--error" role="alert" #errorBox tabindex="-1">{{ error() }}</p>
        }

        <ui-form-field label="E-mail" [error]="emailError()">
          <input uiInput type="email" formControlName="email" autocomplete="username" />
        </ui-form-field>

        <ui-form-field label="Senha" [error]="passwordError()">
          <div class="adm-password">
            <input uiInput [type]="showPassword() ? 'text' : 'password'" formControlName="password" autocomplete="current-password" />
            <button type="button" (click)="showPassword.set(!showPassword())"
                    [attr.aria-label]="showPassword() ? 'Ocultar senha' : 'Mostrar senha'">
              {{ showPassword() ? 'Ocultar' : 'Mostrar' }}
            </button>
          </div>
        </ui-form-field>

        <button uiButton="primary" type="submit" [block]="true" [loading]="loading()">
          {{ loading() ? 'Entrando…' : 'Entrar' }}
        </button>
        <a routerLink="/esqueci-a-senha" class="ui-btn ui-btn--link" style="align-self:center">Esqueci minha senha</a>
      </form>
    </main>
  `,
})
export class LoginPage {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  /** Parâmetros da URL (withComponentInputBinding). */
  readonly retorno = input<string | undefined>();
  readonly expirada = input<string | undefined>();

  protected readonly form = inject(FormBuilder).nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly emailError = signal<string | null>(null);
  protected readonly passwordError = signal<string | null>(null);
  protected readonly showPassword = signal(false);
  private readonly errorBox = viewChild<ElementRef<HTMLElement>>('errorBox');

  protected submit(): void {
    const { email, password } = this.form.getRawValue();
    this.emailError.set(!email ? 'Informe o e-mail.' : this.form.controls.email.invalid ? 'Informe um e-mail válido.' : null);
    this.passwordError.set(!password ? 'Informe a senha.' : null);
    this.error.set(null);
    if (this.emailError() || this.passwordError()) return;

    this.loading.set(true);
    this.auth.login(email, password).subscribe({
      next: () => {
        const target = this.retorno();
        this.router.navigateByUrl(target && target.startsWith('/') && !target.startsWith('//') ? target : '/obras');
      },
      error: (err) => {
        this.loading.set(false);
        const fields = fieldErrors(err);
        this.error.set(fields['email']?.[0] ?? errorMessage(err));
        setTimeout(() => this.errorBox()?.nativeElement.focus());
      },
    });
  }
}
