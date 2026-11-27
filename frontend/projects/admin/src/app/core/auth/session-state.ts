import { Injectable, computed, signal } from '@angular/core';
import { Admin } from '../api/models';

/** Administrador logado (separado do AuthService para o interceptador não criar ciclo). */
@Injectable({ providedIn: 'root' })
export class SessionState {
  private readonly current = signal<Admin | null>(null);
  /** true depois da primeira verificação de sessão (GET auth/me). */
  readonly checked = signal(false);

  readonly admin = this.current.asReadonly();
  readonly loggedIn = computed(() => this.current() !== null);

  set(admin: Admin | null): void {
    this.current.set(admin);
    this.checked.set(true);
  }

  clear(): void {
    this.current.set(null);
  }
}
