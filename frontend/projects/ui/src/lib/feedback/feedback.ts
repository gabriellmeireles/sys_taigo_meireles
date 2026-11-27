import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

/** Bloco cinza no formato do conteúdo que está carregando. */
@Component({
  selector: 'ui-skeleton',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { 'aria-hidden': 'true' },
  template: `
    @for (i of rows(); track i) {
      <span class="ui-skeleton" [class.ui-skeleton--text]="shape() === 'text'"
            [class.ui-skeleton--card]="shape() === 'card'" [style.width]="widthFor(i)"
            [style.height]="shape() === 'block' ? height() : null"></span>
    }
  `,
})
export class UiSkeleton {
  readonly shape = input<'text' | 'card' | 'block'>('text');
  readonly lines = input(1);
  readonly height = input('120px');
  protected readonly rows = computed(() => Array.from({ length: this.lines() }, (_, i) => i));
  protected widthFor(i: number): string | null {
    // Última linha de texto mais curta, como um parágrafo real
    return this.shape() === 'text' && i === this.lines() - 1 && this.lines() > 1 ? '60%' : null;
  }
}

/** Barra de progresso fina (2px). Sem valor = indeterminada. */
@Component({
  selector: 'ui-progress',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    class: 'ui-progress',
    role: 'progressbar',
    '[class.ui-progress--indeterminate]': 'value() === null',
    '[attr.aria-valuenow]': 'value()',
    'aria-valuemin': '0',
    'aria-valuemax': '100',
    '[attr.aria-label]': 'label()',
  },
  template: `<span class="ui-progress__bar" [style.width.%]="value() ?? null"></span>`,
})
export class UiProgress {
  readonly value = input<number | null>(null);
  readonly label = input('Carregando');
}

/**
 * Estados de vazio, erro e sucesso. As ações (botões) entram por projeção.
 *   <ui-state kind="error" title="Não foi possível carregar as obras.">
 *     <button uiButton (click)="tentar()">Tentar novamente</button>
 *   </ui-state>
 */
@Component({
  selector: 'ui-state',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {
    '[class]': '"ui-state ui-state--" + kind()',
    '[attr.role]': 'kind() === "error" ? "alert" : (kind() === "success" ? "status" : null)',
  },
  template: `
    @switch (kind()) {
      @case ('success') { <span class="ui-state__icon" aria-hidden="true">✓</span> }
      @case ('error') { <span class="ui-state__icon" aria-hidden="true">!</span> }
    }
    <p class="ui-state__title" [attr.tabindex]="focusable() ? -1 : null">{{ title() }}</p>
    @if (message()) {
      <p class="ui-state__message">{{ message() }}</p>
    }
    <div class="ui-state__actions"><ng-content /></div>
  `,
})
export class UiState {
  readonly kind = input<'empty' | 'error' | 'success'>('empty');
  readonly title = input.required<string>();
  readonly message = input<string | null>(null);
  /** Permite mover o foco para o título (ex.: após enviar o formulário). */
  readonly focusable = input(false);
}

/** Cabeçalho de seção: eyebrow + título à esquerda, link opcional à direita. */
@Component({
  selector: 'ui-section-heading',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ui-section-heading' },
  template: `
    <div class="ui-stack" style="--stack-gap: var(--space-2)">
      @if (eyebrow()) {
        <span class="ui-eyebrow">{{ eyebrow() }}</span>
      }
      @switch (level()) {
        @case (1) { <h1>{{ title() }}</h1> }
        @case (3) { <h3>{{ title() }}</h3> }
        @default { <h2>{{ title() }}</h2> }
      }
    </div>
    <ng-content />
  `,
})
export class UiSectionHeading {
  readonly title = input.required<string>();
  readonly eyebrow = input<string | null>(null);
  readonly level = input<1 | 2 | 3>(2);
}

/** Indicador numérico do dashboard. */
@Component({
  selector: 'ui-kpi-tile',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ui-kpi' },
  template: `
    <span class="ui-kpi__label">{{ label() }}</span>
    <span class="ui-numeric">{{ value() }}</span>
    @if (delta() !== null) {
      <span class="ui-kpi__delta">
        <span aria-hidden="true">{{ delta()! >= 0 ? '▲' : '▼' }}</span>
        {{ deltaText() }}
      </span>
    }
  `,
})
export class UiKpiTile {
  readonly label = input.required<string>();
  readonly value = input.required<string | number>();
  /** Variação percentual vs. período anterior; null = sem comparação. */
  readonly delta = input<number | null>(null);
  protected readonly deltaText = computed(() => {
    const d = this.delta();
    if (d === null) return '';
    const n = Math.abs(d).toLocaleString('pt-BR', { maximumFractionDigits: 1 });
    return `${d >= 0 ? 'alta de' : 'queda de'} ${n}% vs. período anterior`;
  });
}
