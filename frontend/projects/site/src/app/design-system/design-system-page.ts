import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import {
  PricedArtwork,
  SiteLocale,
  ToastService,
  UiArtworkCard,
  UiButton,
  UiChip,
  UiConfirmDialog,
  UiFormField,
  UiInput,
  UiKpiTile,
  UiLogo,
  UiProgress,
  UiSectionHeading,
  UiSkeleton,
  UiState,
  UiStatusBadge,
  UiTranslationBadge,
  displayPrice,
} from '@tm/ui';

/**
 * Vitrine do design system (docs/04-design-system.md). Página de referência para
 * desenvolvimento e revisão visual; será retirada do site público antes da publicação.
 */
@Component({
  selector: 'tm-design-system-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    UiArtworkCard, UiButton, UiChip, UiConfirmDialog, UiFormField, UiInput, UiKpiTile, UiLogo,
    UiProgress, UiSectionHeading, UiSkeleton, UiState, UiStatusBadge, UiTranslationBadge,
  ],
  templateUrl: './design-system-page.html',
  styleUrl: './design-system-page.scss',
})
export class DesignSystemPage {
  private readonly toasts = inject(ToastService);

  protected readonly colors = [
    { name: 'Ink', hex: '#000000', use: 'Botão primário, foco' },
    { name: 'Charcoal', hex: '#262626', use: 'Texto, rodapé, sidebar' },
    { name: 'Graphite', hex: '#6F6D6E', use: 'Texto secundário, bordas de campo' },
    { name: 'Stone', hex: '#CCC6C3', use: 'Divisores, decoração' },
    { name: 'Mist', hex: '#E7E3E2', use: 'Fundos, molduras, esqueletos' },
    { name: 'Paper', hex: '#FFFFFF', use: 'Fundo principal' },
  ];

  /** Combinações de fundo do manual da marca (versões I–IV e monocromáticas). */
  protected readonly brandBackgrounds = [
    { name: 'Versão I', hex: '#262626', ink: '#FFFFFF' },
    { name: 'Versão II', hex: '#6F6D6E', ink: '#E7E3E2' },
    { name: 'Versão III', hex: '#CCC6C3', ink: '#262626' },
    { name: 'Versão IV', hex: '#E7E3E2', ink: '#6F6D6E' },
    { name: 'Monocromática I', hex: '#FFFFFF', ink: '#000000' },
    { name: 'Monocromática II', hex: '#000000', ink: '#FFFFFF' },
  ];

  protected readonly sending = signal(false);
  protected readonly dialogOpen = signal(false);
  protected readonly chips = signal(['Óleo sobre tela', '2024', 'Série Horizonte']);

  protected readonly priceRows: { label: string; artwork: PricedArtwork }[] = [
    { label: 'Disponível, preço visível', artwork: { status: 'available', showPrice: true, priceBrl: 18000, priceUsd: 3500 } },
    { label: 'Disponível, preço oculto', artwork: { status: 'available', showPrice: false, priceBrl: 18000, priceUsd: 3500 } },
    { label: 'Disponível, sem valor em US$', artwork: { status: 'available', showPrice: true, priceBrl: 9500.5, priceUsd: null } },
    { label: 'Vendida, opção ligada', artwork: { status: 'sold', showPrice: true, priceBrl: 18000, priceUsd: 3500 } },
  ];
  protected readonly locales: SiteLocale[] = ['pt-BR', 'en', 'es'];
  protected readonly price = (a: PricedArtwork, l: SiteLocale) => displayPrice(a, l) ?? '—';

  protected send(): void {
    this.sending.set(true);
    setTimeout(() => {
      this.sending.set(false);
      this.toasts.show('Mensagem enviada.');
    }, 1500);
  }

  protected showError(): void {
    this.toasts.show('Não foi possível salvar. Verifique a conexão e tente novamente.', 'error');
  }

  protected removeChip(chip: string): void {
    this.chips.update((list) => list.filter((c) => c !== chip));
  }

  protected confirmDelete(): void {
    this.dialogOpen.set(false);
    this.toasts.show('Obra excluída.');
  }
}
