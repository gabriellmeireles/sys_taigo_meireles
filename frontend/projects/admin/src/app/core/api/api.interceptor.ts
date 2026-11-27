import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { SessionState } from '../auth/session-state';
import { API_BASE_URL } from './api-config';

/** Lê um cookie pelo nome (o XSRF-TOKEN é legível pelo painel; o de sessão não). */
export function readCookie(name: string, source = document.cookie): string | null {
  const match = source.split('; ').find((c) => c.startsWith(`${name}=`));
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null;
}

/**
 * Requisições para a API: envia o cookie de sessão (withCredentials) e o cabeçalho
 * X-XSRF-TOKEN (o interceptador nativo do Angular só faz isso para a mesma origem).
 * 401/419 fora do login = sessão expirada → volta para a tela de login com retorno.
 */
export const apiInterceptor: HttpInterceptorFn = (req, next) => {
  const base = inject(API_BASE_URL);
  if (!req.url.startsWith(base)) {
    return next(req);
  }

  const session = inject(SessionState);
  const router = inject(Router);
  const xsrf = readCookie('XSRF-TOKEN');

  const request = req.clone({
    withCredentials: true,
    setHeaders: {
      Accept: 'application/json',
      ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
    },
  });

  return next(request).pipe(
    catchError((err: unknown) => {
      const isAuthCall = req.url.includes('/admin/auth/');
      if (err instanceof HttpErrorResponse && (err.status === 401 || err.status === 419) && !isAuthCall) {
        session.clear();
        router.navigate(['/entrar'], { queryParams: { retorno: router.url, expirada: 1 } });
      }
      return throwError(() => err);
    }),
  );
};
