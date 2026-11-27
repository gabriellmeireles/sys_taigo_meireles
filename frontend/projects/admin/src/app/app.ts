import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { UiToastOutlet } from '@tm/ui';

@Component({
  selector: 'adm-root',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterOutlet, UiToastOutlet],
  template: `
    <router-outlet />
    <ui-toast-outlet />
  `,
})
export class App {}
