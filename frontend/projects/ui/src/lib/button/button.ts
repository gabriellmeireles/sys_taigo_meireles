import { Directive, ElementRef, computed, inject, input } from '@angular/core';

export type ButtonVariant = 'primary' | 'secondary' | 'inverse' | 'link' | 'channel';

/**
 * Botão do design system. Usar em <button> ou <a>:
 *   <button uiButton="primary" [loading]="enviando()">Enviar</button>
 * Enquanto carrega, o botão fica desabilitado e anunciado como ocupado.
 */
@Directive({
  selector: 'button[uiButton], a[uiButton]',
  host: {
    class: 'ui-btn',
    '[class]': 'variantClass()',
    '[class.ui-btn--block]': 'block()',
    '[class.ui-btn--loading]': 'loading()',
    '[attr.aria-busy]': 'loading() || null',
    '[attr.disabled]': 'isButton && blocked() ? "" : null',
    '[attr.aria-disabled]': '!isButton && blocked() ? "true" : null',
    '[attr.tabindex]': '!isButton && blocked() ? -1 : null',
  },
})
export class UiButton {
  readonly variant = input<ButtonVariant | ''>('secondary', { alias: 'uiButton' });
  readonly loading = input(false);
  readonly disabled = input(false);
  readonly block = input(false);

  protected readonly isButton =
    inject<ElementRef<HTMLElement>>(ElementRef).nativeElement.tagName === 'BUTTON';

  protected readonly blocked = computed(() => this.loading() || this.disabled());
  protected readonly variantClass = computed(() => `ui-btn--${this.variant() || 'secondary'}`);
}
