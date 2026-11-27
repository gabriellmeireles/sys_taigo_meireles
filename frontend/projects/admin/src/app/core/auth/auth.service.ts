import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, map, of, switchMap, tap } from 'rxjs';
import { API_BASE_URL } from '../api/api-config';
import { Admin } from '../api/models';
import { SessionState } from './session-state';

/** Login por sessão (Laravel Sanctum, modo SPA). docs/05-arquitetura.md §6.6. */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly base = inject(API_BASE_URL);
  private readonly session = inject(SessionState);

  private url(path: string): string {
    return `${this.base}/api/v1/admin/auth/${path}`;
  }

  /** Pede o cookie XSRF-TOKEN antes de requisições que alteram o estado sem sessão. */
  csrf(): Observable<void> {
    return this.http.get<void>(`${this.base}/sanctum/csrf-cookie`);
  }

  /** Verifica se já existe sessão (ao abrir o painel). */
  restore(): Observable<Admin | null> {
    if (this.session.checked()) {
      return of(this.session.admin());
    }
    return this.http.get<{ data: Admin }>(this.url('me')).pipe(
      map((r) => r.data),
      catchError(() => of(null)),
      tap((admin) => this.session.set(admin)),
    );
  }

  login(email: string, password: string): Observable<Admin> {
    return this.csrf().pipe(
      switchMap(() => this.http.post<{ data: Admin }>(this.url('login'), { email, password })),
      map((r) => r.data),
      tap((admin) => this.session.set(admin)),
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>(this.url('logout'), {}).pipe(
      catchError(() => of(undefined)),
      tap(() => this.session.set(null)),
    );
  }

  forgotPassword(email: string): Observable<{ message: string }> {
    return this.csrf().pipe(switchMap(() => this.http.post<{ message: string }>(this.url('forgot-password'), { email })));
  }

  /** mode "reset" (esqueci a senha) ou "invite" (primeiro acesso por convite). */
  setPassword(
    mode: 'reset' | 'invite',
    data: { token: string; email: string; password: string; password_confirmation: string },
  ): Observable<{ message: string }> {
    const path = mode === 'reset' ? 'reset-password' : 'accept-invite';
    return this.csrf().pipe(switchMap(() => this.http.post<{ message: string }>(this.url(path), data)));
  }
}
