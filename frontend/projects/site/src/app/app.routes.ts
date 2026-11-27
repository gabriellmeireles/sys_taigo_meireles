import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: 'design-system',
    title: 'Design system — Taigo Meireles',
    loadComponent: () => import('./design-system/design-system-page').then((m) => m.DesignSystemPage),
  },
  // Temporário: até a etapa 5 (Início e rotas por idioma), a raiz abre a vitrine do design system.
  { path: '', pathMatch: 'full', redirectTo: 'design-system' },
];
