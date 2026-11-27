import { HttpErrorResponse } from '@angular/common/http';
import { FieldErrors } from './models';

/** Mensagem legível para qualquer erro de requisição. */
export function errorMessage(err: unknown, fallback = 'Não foi possível concluir. Tente novamente.'): string {
  if (err instanceof HttpErrorResponse) {
    if (err.status === 0) return 'Sem conexão com o servidor. Verifique a internet e tente de novo.';
    const body = err.error as { message?: string; errors?: FieldErrors } | null;
    const first = body?.errors ? Object.values(body.errors)[0]?.[0] : null;
    if (err.status === 422 && first) return first;
    if (err.status === 429) return first ?? 'Muitas tentativas. Aguarde um pouco e tente de novo.';
    if (err.status >= 500) return 'O servidor encontrou um erro. Tente novamente em instantes.';
    if (body?.message && err.status < 500) return body.message;
  }
  return fallback;
}

/** Erros de campo de uma resposta 422 (ou vazio). */
export function fieldErrors(err: unknown): FieldErrors {
  if (err instanceof HttpErrorResponse && (err.status === 422 || err.status === 429)) {
    return (err.error?.errors as FieldErrors) ?? {};
  }
  return {};
}
