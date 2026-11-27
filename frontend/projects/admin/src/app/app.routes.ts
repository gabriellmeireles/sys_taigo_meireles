import { Routes } from '@angular/router';
import { authGuard, guestGuard, unsavedChangesGuard } from './core/auth/guards';

const t = (title: string) => `${title} — Painel Taigo Meireles`;

export const routes: Routes = [
  // Acesso
  {
    path: 'entrar',
    title: t('Entrar'),
    canActivate: [guestGuard],
    loadComponent: () => import('./features/auth/login-page').then((m) => m.LoginPage),
  },
  {
    path: 'esqueci-a-senha',
    title: t('Esqueci minha senha'),
    canActivate: [guestGuard],
    loadComponent: () => import('./features/auth/forgot-password-page').then((m) => m.ForgotPasswordPage),
  },
  {
    path: 'redefinir-senha',
    title: t('Nova senha'),
    data: { mode: 'reset' },
    loadComponent: () => import('./features/auth/set-password-page').then((m) => m.SetPasswordPage),
  },
  {
    path: 'definir-senha',
    title: t('Definir senha'),
    data: { mode: 'invite' },
    loadComponent: () => import('./features/auth/set-password-page').then((m) => m.SetPasswordPage),
  },

  // Área autenticada
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/shell').then((m) => m.Shell),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'obras' },
      {
        path: 'obras',
        title: t('Obras'),
        loadComponent: () => import('./features/artworks/artwork-list-page').then((m) => m.ArtworkListPage),
      },
      {
        path: 'obras/nova',
        title: t('Nova obra'),
        canDeactivate: [unsavedChangesGuard],
        loadComponent: () => import('./features/artworks/artwork-editor-page').then((m) => m.ArtworkEditorPage),
      },
      {
        path: 'obras/:id',
        title: t('Editar obra'),
        canDeactivate: [unsavedChangesGuard],
        loadComponent: () => import('./features/artworks/artwork-editor-page').then((m) => m.ArtworkEditorPage),
      },
      {
        path: 'series',
        title: t('Séries'),
        data: { kind: 'series', title: 'Séries', noun: 'série' },
        loadComponent: () => import('./features/taxonomies/taxonomy-page').then((m) => m.TaxonomyPage),
      },
      {
        path: 'tecnicas',
        title: t('Técnicas'),
        data: { kind: 'techniques', title: 'Técnicas', noun: 'técnica' },
        loadComponent: () => import('./features/taxonomies/taxonomy-page').then((m) => m.TaxonomyPage),
      },
      {
        path: 'categorias',
        title: t('Categorias'),
        data: { kind: 'categories', title: 'Categorias', noun: 'categoria' },
        loadComponent: () => import('./features/taxonomies/taxonomy-page').then((m) => m.TaxonomyPage),
      },
    ],
  },
  { path: '**', redirectTo: 'obras' },
];
