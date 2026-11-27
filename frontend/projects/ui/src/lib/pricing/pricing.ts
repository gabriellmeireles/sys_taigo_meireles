/**
 * Regras de preço e moeda do site (docs/06-interacao-responsividade-estados.md §7.5).
 *  - pt-BR mostra reais (R$); en e es mostram dólares (US$);
 *  - não há conversão: os dois valores são cadastrados à mão;
 *  - o preço aparece só com "exibir preço" ligado, valor na moeda do idioma
 *    e, por padrão, obra disponível (P-05, configurável).
 */

export type SiteLocale = 'pt-BR' | 'en' | 'es';
export type Currency = 'BRL' | 'USD';

export interface PricedArtwork {
  status: 'available' | 'reserved' | 'sold';
  showPrice: boolean;
  priceBrl: number | null;
  priceUsd: number | null;
}

export function currencyFor(locale: SiteLocale): Currency {
  return locale === 'pt-BR' ? 'BRL' : 'USD';
}

/** R$ 18.000 · US$ 3,500 · US$ 3.500 (centavos só quando existem). */
export function formatPrice(amount: number, locale: SiteLocale): string {
  const hasCents = Math.round(amount * 100) % 100 !== 0;
  const number = new Intl.NumberFormat(locale, {
    minimumFractionDigits: hasCents ? 2 : 0,
    maximumFractionDigits: 2,
    // 'always': em espanhol o padrão não separa milhares com 4 dígitos (3500 → 3.500).
    // Os tipos da lib ES2022 só conhecem boolean; o valor é suportado pelos navegadores atuais.
    useGrouping: 'always' as unknown as boolean,
  }).format(amount);
  return `${currencyFor(locale) === 'BRL' ? 'R$' : 'US$'} ${number}`;
}

/** Preço já formatado, ou null quando a obra não deve mostrar preço. */
export function displayPrice(
  artwork: PricedArtwork,
  locale: SiteLocale,
  hidePriceUnlessAvailable = true,
): string | null {
  if (!artwork.showPrice) return null;
  if (hidePriceUnlessAvailable && artwork.status !== 'available') return null;
  const amount = currencyFor(locale) === 'BRL' ? artwork.priceBrl : artwork.priceUsd;
  return amount === null || amount === undefined ? null : formatPrice(amount, locale);
}
