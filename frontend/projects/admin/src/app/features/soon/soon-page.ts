import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { UiButton, UiState } from '@tm/ui';

/** Telas que chegam nas próximas etapas. */
@Component({
  selector: 'adm-soon-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, UiButton, UiState],
  template: `
    <ui-state [title]="title()" message="Esta tela chega numa próxima etapa do desenvolvimento.">
      <a routerLink="/obras" uiButton="secondary">Ir para as obras</a>
    </ui-state>
  `,
})
export class SoonPage {
  readonly title = input('Em breve');
}
