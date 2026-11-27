import {
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  effect,
  input,
  output,
  viewChild,
} from '@angular/core';
import { UiButton } from '../button/button';

/**
 * Confirmação antes de ações destrutivas, sobre o <dialog> nativo
 * (foco preso, Esc fecha, foco volta ao elemento de origem).
 *   <ui-confirm-dialog [open]="aberto()" title="Excluir 'Maré'?"
 *     message="Esta ação não pode ser desfeita." confirmLabel="Excluir" [destructive]="true"
 *     (confirmed)="excluir()" (closed)="aberto.set(false)" />
 */
@Component({
  selector: 'ui-confirm-dialog',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [UiButton],
  template: `
    <dialog #dlg class="ui-dialog" [attr.aria-labelledby]="titleId" (close)="closed.emit()">
      <div class="ui-dialog__body">
        <h2 class="ui-dialog__title" [id]="titleId">{{ title() }}</h2>
        @if (message()) {
          <p>{{ message() }}</p>
        }
        <div class="ui-dialog__actions">
          <button uiButton="secondary" type="button" (click)="dlg.close()">{{ cancelLabel() }}</button>
          <button uiButton="primary" type="button" [loading]="busy()" (click)="confirmed.emit()">
            {{ confirmLabel() }}
          </button>
        </div>
      </div>
    </dialog>
  `,
})
export class UiConfirmDialog {
  private static counter = 0;
  protected readonly titleId = `ui-dialog-${UiConfirmDialog.counter++}`;

  readonly open = input(false);
  readonly title = input.required<string>();
  readonly message = input<string | null>(null);
  readonly confirmLabel = input('Confirmar');
  readonly cancelLabel = input('Cancelar');
  readonly busy = input(false);

  readonly confirmed = output<void>();
  readonly closed = output<void>();

  private readonly dialog = viewChild.required<ElementRef<HTMLDialogElement>>('dlg');

  constructor() {
    effect(() => {
      const el = this.dialog().nativeElement;
      if (this.open() && !el.open) {
        el.showModal();
      } else if (!this.open() && el.open) {
        el.close();
      }
    });
  }
}
