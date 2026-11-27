import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { UiLogo, UiState, UiToastOutlet } from '@tm/ui';

/** Estrutura provisória: o shell do painel e a tela de login chegam na etapa 3. */
@Component({
  selector: 'adm-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, UiLogo, UiState, UiToastOutlet],
  template: `
    <main class="adm-placeholder">
      <ui-logo class="adm-brand" variant="selo" label="Taigo Meireles — painel" />
      <ui-state title="Painel em construção" message="A tela de login e o cadastro de obras chegam na próxima etapa." />
      <router-outlet />
    </main>
    <ui-toast-outlet />
  `,
  styles: `
    .adm-placeholder { min-height: 100dvh; display: grid; place-content: center; gap: var(--space-4); text-align: center; }
    .adm-brand { width: 160px; margin-inline: auto; color: var(--color-charcoal); }
  `,
})
export class App {}
