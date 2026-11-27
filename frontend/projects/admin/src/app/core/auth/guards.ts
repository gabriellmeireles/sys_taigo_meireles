import { inject } from '@angular/core';
import { CanActivateFn, CanDeactivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { AuthService } from './auth.service';

/** Área do painel: exige sessão; sem ela, vai para o login guardando o destino. */
export const authGuard: CanActivateFn = (_route, state) => {
  const router = inject(Router);
  return inject(AuthService)
    .restore()
    .pipe(map((admin) => admin ? true : router.createUrlTree(['/entrar'], { queryParams: { retorno: state.url } })));
};

/** Telas de login/senha: quem já está logado vai direto para as obras. */
export const guestGuard: CanActivateFn = () => {
  const router = inject(Router);
  return inject(AuthService)
    .restore()
    .pipe(map((admin) => (admin ? router.createUrlTree(['/obras']) : true)));
};

/** Componentes com formulário informam se há alterações não salvas. */
export interface HasUnsavedChanges {
  hasUnsavedChanges(): boolean;
}

export const unsavedChangesGuard: CanDeactivateFn<HasUnsavedChanges> = (component) =>
  !component.hasUnsavedChanges() || confirm('Há alterações não salvas. Sair mesmo assim?');
