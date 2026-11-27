import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ArtworkStatus, UiStatusBadge } from '../badges/badges';

/**
 * Card de obra para grids: moldura 4:5 com a obra contida (sem corte),
 * título, ano · técnica, status e preço opcional. O card inteiro é um único link.
 */
@Component({
  selector: 'ui-artwork-card',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, UiStatusBadge],
  template: `
    <a class="ui-artwork-card" [routerLink]="link()">
      <div class="ui-artwork-card__frame" [class.ui-artwork-card__frame--mist]="frame() === 'mist'"
           [style.background-color]="placeholderColor()">
        <img class="ui-artwork-card__img" [src]="imageSrc()" [attr.srcset]="imageSrcset()"
             [attr.sizes]="sizes()" [alt]="imageAlt() ?? ''" [attr.width]="imageWidth()"
             [attr.height]="imageHeight()" [attr.loading]="eager() ? 'eager' : 'lazy'" decoding="async" />
      </div>
      <span class="ui-artwork-card__title">{{ title() }}</span>
      <span class="ui-artwork-card__meta">{{ meta() }}</span>
      @if (status(); as s) {
        <ui-status-badge [status]="s" [label]="statusLabel()" />
      }
      @if (price()) {
        <span class="ui-artwork-card__price">{{ price() }}</span>
      }
    </a>
  `,
})
export class UiArtworkCard {
  readonly link = input.required<string | unknown[]>();
  readonly title = input.required<string>();
  /** "2024 · Óleo sobre tela" */
  readonly meta = input('');
  readonly imageSrc = input.required<string>();
  readonly imageSrcset = input<string | null>(null);
  readonly sizes = input('(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw');
  readonly imageAlt = input<string | null>(null);
  readonly imageWidth = input<number | null>(null);
  readonly imageHeight = input<number | null>(null);
  readonly placeholderColor = input<string | null>(null);
  readonly frame = input<'white' | 'mist'>('white');
  readonly status = input<ArtworkStatus | null>(null);
  readonly statusLabel = input<string | null>(null);
  /** Já formatado (ver formatPrice). Vazio = não mostra. */
  readonly price = input<string | null>(null);
  readonly eager = input(false);
}
