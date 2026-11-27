import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { filter } from 'rxjs';
import { UiLogo } from '@tm/ui';
import { SITE_URL } from '../core/api/api-config';
import { AuthService } from '../core/auth/auth.service';
import { SessionState } from '../core/auth/session-state';

interface NavItem {
  label: string;
  link?: string;
}

/** Estrutura do painel (tela 4.0): menu lateral escuro + barra superior. */
@Component({
  selector: 'adm-shell',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, UiLogo],
  templateUrl: './shell.html',
})
export class Shell {
  protected readonly session = inject(SessionState);
  protected readonly siteUrl = inject(SITE_URL);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly menuOpen = signal(false);

  /** Itens sem link chegam nas próximas etapas. */
  protected readonly groups: { title: string | null; items: NavItem[] }[] = [
    { title: null, items: [{ label: 'Dashboard' }, { label: 'Mensagens' }] },
    {
      title: 'Conteúdo',
      items: [
        { label: 'Obras', link: '/obras' },
        { label: 'Séries', link: '/series' },
        { label: 'Técnicas', link: '/tecnicas' },
        { label: 'Categorias', link: '/categorias' },
        { label: 'Páginas' },
        { label: 'Exposições' },
        { label: 'Ateliê' },
        { label: 'Imprensa' },
        { label: 'Mídia' },
      ],
    },
    { title: 'Idiomas', items: [{ label: 'Traduções', link: '/traducoes' }, { label: 'SEO' }] },
    {
      title: 'Ajustes',
      items: [{ label: 'Contato' }, { label: 'Respostas automáticas' }, { label: 'Site' }, { label: 'Administradores' }],
    },
  ];

  constructor() {
    // No celular, o menu fecha ao navegar
    this.router.events
      .pipe(filter((e) => e instanceof NavigationEnd), takeUntilDestroyed())
      .subscribe(() => this.menuOpen.set(false));
  }

  protected logout(): void {
    this.auth.logout().subscribe(() => this.router.navigate(['/entrar']));
  }
}
