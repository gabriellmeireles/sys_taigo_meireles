import { ChangeDetectionStrategy, Component, Injectable, inject, signal } from '@angular/core';

export type ToastKind = 'success' | 'info' | 'error';

export interface Toast {
  id: number;
  message: string;
  kind: ToastKind;
}

/** Avisos curtos no canto da tela ("Obra salva e publicada."). */
@Injectable({ providedIn: 'root' })
export class ToastService {
  private nextId = 1;
  private readonly items = signal<Toast[]>([]);
  readonly toasts = this.items.asReadonly();

  /** duration em ms; 0 = fica até ser fechado. Erros ficam por padrão. */
  show(message: string, kind: ToastKind = 'success', duration = kind === 'error' ? 0 : 4000): number {
    const id = this.nextId++;
    this.items.update((list) => [...list, { id, message, kind }]);
    if (duration > 0) {
      setTimeout(() => this.dismiss(id), duration);
    }
    return id;
  }

  dismiss(id: number): void {
    this.items.update((list) => list.filter((t) => t.id !== id));
  }
}

/**
 * Colocar uma vez no componente raiz de cada app.
 * Sucesso e informação são anunciados com educação (status); erro, na hora (alert).
 */
@Component({
  selector: 'ui-toast-outlet',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ui-toasts' },
  template: `
    @for (t of service.toasts(); track t.id) {
      <div class="ui-toast" [class.ui-toast--error]="t.kind === 'error'"
           [attr.role]="t.kind === 'error' ? 'alert' : 'status'">
        <span class="ui-toast__text">{{ t.message }}</span>
        <button type="button" class="ui-toast__close" [attr.aria-label]="closeLabel"
                (click)="service.dismiss(t.id)">✕</button>
      </div>
    }
  `,
})
export class UiToastOutlet {
  protected readonly service = inject(ToastService);
  protected readonly closeLabel = 'Fechar aviso';
}
