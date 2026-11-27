import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

export type LogoVariant = 'horizontal' | 'assinatura' | 'selo' | 'mao';

/**
 * Marca Taigo Meireles (manual da marca). Desenhada com a cor do texto (currentColor),
 * então serve em fundo claro e escuro sem arquivos diferentes:
 *   <ui-logo variant="horizontal" />                 cabeçalho
 *   <ui-logo variant="selo" class="ui-on-dark" />    fundo #262626 (versão I)
 * Nunca usar sobre fotos de obras (sem marca d'água — requisito).
 */
@Component({
  selector: 'ui-logo',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    '[class]': '"ui-logo ui-logo--" + variant()',
    '[style.--ui-logo-src]': 'source()',
    '[attr.role]': 'decorative() ? null : "img"',
    '[attr.aria-label]': 'decorative() ? null : label()',
    '[attr.aria-hidden]': 'decorative() ? "true" : null',
  },
  template: '',
})
export class UiLogo {
  readonly variant = input<LogoVariant>('horizontal');
  readonly label = input('Taigo Meireles');
  /** true quando o nome já aparece em texto ao lado (evita leitura duplicada). */
  readonly decorative = input(false);
  /** Pasta onde os SVGs da marca são servidos (angular.json → assets "brand"). */
  readonly basePath = input('brand');

  protected readonly source = computed(() => `url("${this.basePath()}/${this.variant()}.svg")`);
}
