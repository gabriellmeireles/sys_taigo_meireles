import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { UiToastOutlet } from '@tm/ui';

@Component({
  selector: 'tm-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, UiToastOutlet],
  template: `
    <a class="ui-skip-link" href="#conteudo">Ir para o conteúdo</a>
    <main id="conteudo" tabindex="-1">
      <router-outlet />
    </main>
    <ui-toast-outlet />
  `,
})
export class App {}
