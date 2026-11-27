import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';

export type ArtworkStatus = 'available' | 'reserved' | 'sold';
export type TranslationState = 'manual' | 'auto' | 'outdated' | 'missing';

const STATUS_LABELS: Record<ArtworkStatus, string> = {
  available: 'Disponível',
  reserved: 'Reservada',
  sold: 'Vendida',
};

const TRANSLATION_LABELS: Record<TranslationState, string> = {
  manual: 'Revisada',
  auto: 'Automática',
  outdated: 'Desatualizada',
  missing: 'Ausente',
};

/** Status da obra: sempre com texto, nunca só cor. O site passa o rótulo traduzido. */
@Component({
  selector: 'ui-status-badge',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { '[class]': '"ui-status ui-status--" + status()' },
  template: `{{ text() }}`,
})
export class UiStatusBadge {
  readonly status = input.required<ArtworkStatus>();
  readonly label = input<string | null>(null);
  protected readonly text = computed(() => this.label() ?? STATUS_LABELS[this.status()]);
}

/** Estado da tradução no painel. */
@Component({
  selector: 'ui-translation-badge',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { '[class]': '"ui-tbadge ui-tbadge--" + state()' },
  template: `
    @switch (state()) {
      @case ('manual') { <span aria-hidden="true">✓</span> }
      @case ('auto') { <span aria-hidden="true">◌</span> }
      @case ('outdated') { <span aria-hidden="true">!</span> }
      @case ('missing') { <span aria-hidden="true">—</span> }
    }
    {{ prefix() }}{{ labels[state()] }}
  `,
})
export class UiTranslationBadge {
  readonly state = input.required<TranslationState>();
  /** Ex.: "EN " para mostrar o idioma antes do estado. */
  readonly prefix = input('');
  protected readonly labels = TRANSLATION_LABELS;
}

/** Filtro ativo removível. */
@Component({
  selector: 'ui-chip',
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'ui-chip' },
  template: `
    <span><ng-content /></span>
    <button type="button" [attr.aria-label]="removeLabel()" (click)="remove.emit()">✕</button>
  `,
})
export class UiChip {
  readonly removeLabel = input('Remover filtro');
  readonly remove = output<void>();
}
