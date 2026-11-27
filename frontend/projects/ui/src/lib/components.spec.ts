import { Component, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { UiStatusBadge, UiTranslationBadge } from './badges/badges';
import { UiButton } from './button/button';
import { UiFormField, UiInput } from './form-field/form-field';
import { UiLogo } from './logo/logo';
import { ToastService, UiToastOutlet } from './toast/toast';

@Component({
  imports: [UiButton],
  template: `
    <button uiButton="primary" [loading]="loading()">Enviar</button>
    <a uiButton="link" href="#" [disabled]="true">Link</a>
  `,
})
class ButtonHost {
  readonly loading = signal(false);
}

@Component({
  imports: [UiFormField, UiInput],
  template: `
    <ui-form-field label="E-mail" hint="Usamos só para responder." [error]="error()" [required]="true">
      <input uiInput type="email" />
    </ui-form-field>
  `,
})
class FieldHost {
  readonly error = signal<string | null>(null);
}

@Component({
  imports: [UiStatusBadge, UiTranslationBadge],
  template: `
    <ui-status-badge status="reserved" />
    <ui-status-badge status="available" label="Available" />
    <ui-translation-badge state="outdated" prefix="EN " />
  `,
})
class BadgeHost {}

describe('UiButton', () => {
  it('desabilita e anuncia ocupado enquanto carrega', async () => {
    const fixture = TestBed.createComponent(ButtonHost);
    await fixture.whenStable();
    const button = fixture.nativeElement.querySelector('button') as HTMLButtonElement;
    expect(button.classList).toContain('ui-btn');
    expect(button.classList).toContain('ui-btn--primary');
    expect(button.disabled).toBe(false);

    fixture.componentInstance.loading.set(true);
    await fixture.whenStable();
    expect(button.disabled).toBe(true);
    expect(button.getAttribute('aria-busy')).toBe('true');
    expect(button.classList).toContain('ui-btn--loading');
  });

  it('em links usa aria-disabled em vez de disabled', async () => {
    const fixture = TestBed.createComponent(ButtonHost);
    await fixture.whenStable();
    const link = fixture.nativeElement.querySelector('a') as HTMLAnchorElement;
    expect(link.getAttribute('aria-disabled')).toBe('true');
    expect(link.hasAttribute('disabled')).toBe(false);
  });
});

describe('UiFormField + UiInput', () => {
  it('liga rótulo, ajuda e erro ao controle', async () => {
    const fixture = TestBed.createComponent(FieldHost);
    await fixture.whenStable();
    const el = fixture.nativeElement as HTMLElement;
    const input = el.querySelector('input')!;
    const label = el.querySelector('label')!;

    expect(label.getAttribute('for')).toBe(input.id);
    expect(input.getAttribute('aria-required')).toBe('true');
    expect(input.getAttribute('aria-invalid')).toBeNull();
    const hintId = input.getAttribute('aria-describedby')!;
    expect(el.querySelector(`#${hintId}`)?.textContent).toContain('Usamos só para responder.');

    fixture.componentInstance.error.set('Informe um e-mail válido.');
    await fixture.whenStable();
    expect(input.getAttribute('aria-invalid')).toBe('true');
    const ids = input.getAttribute('aria-describedby')!.split(' ');
    expect(ids).toHaveLength(2);
    expect(el.querySelector(`#${ids[1]}`)?.textContent).toContain('Informe um e-mail válido.');
  });
});

describe('Badges', () => {
  it('mostram sempre texto, com rótulo padrão em português ou o rótulo traduzido', async () => {
    const fixture = TestBed.createComponent(BadgeHost);
    await fixture.whenStable();
    const [reserved, available] = Array.from(
      (fixture.nativeElement as HTMLElement).querySelectorAll('ui-status-badge'),
    );
    expect(reserved.textContent?.trim()).toBe('Reservada');
    expect(reserved.classList).toContain('ui-status--reserved');
    expect(available.textContent?.trim()).toBe('Available');
    expect(fixture.nativeElement.querySelector('ui-translation-badge').textContent).toContain('EN Desatualizada');
  });
});

@Component({
  imports: [UiLogo],
  template: `
    <ui-logo variant="selo" />
    <ui-logo variant="mao" [decorative]="true" />
  `,
})
class LogoHost {}

describe('UiLogo', () => {
  it('é uma imagem com nome acessível, ou fica oculta quando decorativa', async () => {
    const fixture = TestBed.createComponent(LogoHost);
    await fixture.whenStable();
    const [selo, mao] = Array.from((fixture.nativeElement as HTMLElement).querySelectorAll('ui-logo')) as HTMLElement[];

    expect(selo.getAttribute('role')).toBe('img');
    expect(selo.getAttribute('aria-label')).toBe('Taigo Meireles');
    expect(selo.classList).toContain('ui-logo--selo');
    expect(selo.style.getPropertyValue('--ui-logo-src')).toContain('brand/selo.svg');

    expect(mao.getAttribute('role')).toBeNull();
    expect(mao.getAttribute('aria-hidden')).toBe('true');
  });
});

describe('ToastService', () => {
  it('mostra, anuncia e remove avisos', async () => {
    const fixture = TestBed.createComponent(UiToastOutlet);
    const service = TestBed.inject(ToastService);

    const id = service.show('Obra salva e publicada.', 'success', 0);
    service.show('Falha ao salvar.', 'error');
    await fixture.whenStable();

    const toasts = (fixture.nativeElement as HTMLElement).querySelectorAll('.ui-toast');
    expect(toasts).toHaveLength(2);
    expect(toasts[0].getAttribute('role')).toBe('status');
    expect(toasts[1].getAttribute('role')).toBe('alert');

    service.dismiss(id);
    await fixture.whenStable();
    expect(service.toasts()).toHaveLength(1);
  });

  it('some sozinho depois do tempo definido', () => {
    vi.useFakeTimers();
    const service = TestBed.inject(ToastService);
    service.show('Salvo.', 'success', 1000);
    expect(service.toasts()).toHaveLength(1);
    vi.advanceTimersByTime(1000);
    expect(service.toasts()).toHaveLength(0);
    vi.useRealTimers();
  });
});
