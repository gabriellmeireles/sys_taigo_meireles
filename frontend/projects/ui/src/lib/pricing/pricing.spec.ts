import { PricedArtwork, currencyFor, displayPrice, formatPrice } from './pricing';

const nbsp = ' ';

describe('formatPrice', () => {
  it('usa R$ em português e US$ em inglês e espanhol', () => {
    expect(formatPrice(18000, 'pt-BR')).toBe(`R$${nbsp}18.000`);
    expect(formatPrice(3500, 'en')).toBe(`US$${nbsp}3,500`);
    expect(formatPrice(3500, 'es')).toBe(`US$${nbsp}3.500`);
  });

  it('mostra centavos só quando existem', () => {
    expect(formatPrice(9500.5, 'pt-BR')).toBe(`R$${nbsp}9.500,50`);
    expect(formatPrice(1200.25, 'en')).toBe(`US$${nbsp}1,200.25`);
  });

  it('associa a moeda ao idioma', () => {
    expect(currencyFor('pt-BR')).toBe('BRL');
    expect(currencyFor('en')).toBe('USD');
    expect(currencyFor('es')).toBe('USD');
  });
});

describe('displayPrice', () => {
  const base: PricedArtwork = { status: 'available', showPrice: true, priceBrl: 18000, priceUsd: 3500 };

  it('mostra o preço da moeda do idioma quando permitido', () => {
    expect(displayPrice(base, 'pt-BR')).toBe(`R$${nbsp}18.000`);
    expect(displayPrice(base, 'en')).toBe(`US$${nbsp}3,500`);
  });

  it('esconde quando "exibir preço" está desligado', () => {
    expect(displayPrice({ ...base, showPrice: false }, 'pt-BR')).toBeNull();
  });

  it('esconde quando falta o valor na moeda do idioma', () => {
    expect(displayPrice({ ...base, priceUsd: null }, 'es')).toBeNull();
    expect(displayPrice({ ...base, priceUsd: null }, 'pt-BR')).toBe(`R$${nbsp}18.000`);
  });

  it('esconde para reservada e vendida por padrão (P-05), mas a regra é configurável', () => {
    expect(displayPrice({ ...base, status: 'sold' }, 'pt-BR')).toBeNull();
    expect(displayPrice({ ...base, status: 'reserved' }, 'en')).toBeNull();
    expect(displayPrice({ ...base, status: 'sold' }, 'pt-BR', false)).toBe(`R$${nbsp}18.000`);
  });
});
