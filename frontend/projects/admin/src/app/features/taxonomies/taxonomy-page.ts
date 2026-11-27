import { ChangeDetectionStrategy, Component, ElementRef, effect, inject, input, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import {
  ToastService,
  UiButton,
  UiConfirmDialog,
  UiFormField,
  UiInput,
  UiSkeleton,
  UiState,
  UiTranslationBadge,
} from '@tm/ui';
import { CatalogApi } from '../../core/api/catalog.api';
import { errorMessage, fieldErrors } from '../../core/api/errors';
import { Taxonomy, TaxonomyKind } from '../../core/api/models';

/** Tela 4.7 — Categorias, técnicas e séries (mesmo componente, configurado pela rota). */
@Component({
  selector: 'adm-taxonomy-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, UiButton, UiConfirmDialog, UiFormField, UiInput, UiSkeleton, UiState, UiTranslationBadge],
  templateUrl: './taxonomy-page.html',
})
export class TaxonomyPage {
  private readonly api = inject(CatalogApi);
  private readonly toasts = inject(ToastService);

  // data da rota
  readonly kind = input.required<TaxonomyKind>();
  readonly title = input.required<string>();
  /** Singular, ex.: "série" / "técnica" / "categoria". */
  readonly noun = input.required<string>();
  /** Gênero para "Nova"/"Novo". */
  readonly feminine = input(true);

  protected readonly items = signal<Taxonomy[] | null>(null);
  protected readonly loadError = signal<string | null>(null);
  protected readonly editing = signal<Taxonomy | 'new' | null>(null);
  protected readonly saving = signal(false);
  protected readonly nameError = signal<string | null>(null);
  protected readonly toDelete = signal<Taxonomy | null>(null);
  protected readonly deleting = signal(false);

  protected readonly form = inject(FormBuilder).nonNullable.group({ pt: [''], en: [''], es: [''] });
  private readonly firstField = viewChild('firstField', { read: ElementRef<HTMLElement> });

  constructor() {
    effect(() => {
      this.kind();
      this.load();
    });
  }

  protected load(): void {
    this.items.set(null);
    this.loadError.set(null);
    this.api.taxonomies(this.kind()).subscribe({
      next: (list) => this.items.set(list),
      error: (err) => this.loadError.set(errorMessage(err, `Não foi possível carregar.`)),
    });
  }

  protected startNew(): void {
    this.form.reset({ pt: '', en: '', es: '' });
    this.nameError.set(null);
    this.editing.set('new');
    setTimeout(() => this.firstField()?.nativeElement.querySelector('input')?.focus());
  }

  protected startEdit(item: Taxonomy): void {
    this.form.reset({
      pt: item.translations['pt-BR']?.name ?? '',
      en: item.translations.en?.name ?? '',
      es: item.translations.es?.name ?? '',
    });
    this.nameError.set(null);
    this.editing.set(item);
    setTimeout(() => this.firstField()?.nativeElement.querySelector('input')?.focus());
  }

  protected save(): void {
    const { pt, en, es } = this.form.getRawValue();
    if (!pt.trim()) {
      this.nameError.set('Informe o nome em português.');
      return;
    }
    const current = this.editing();
    const id = current && current !== 'new' ? current.id : null;
    this.saving.set(true);
    this.api.saveTaxonomy(this.kind(), id, { 'pt-BR': pt, en, es }).subscribe({
      next: () => {
        this.saving.set(false);
        this.editing.set(null);
        this.toasts.show(`${this.capitalized()} salva.`);
        this.load();
      },
      error: (err) => {
        this.saving.set(false);
        this.nameError.set(fieldErrors(err)['translations.pt-BR.name']?.[0] ?? null);
        if (!this.nameError()) this.toasts.show(errorMessage(err), 'error');
      },
    });
  }

  protected move(index: number, delta: -1 | 1): void {
    const list = [...(this.items() ?? [])];
    const target = index + delta;
    if (target < 0 || target >= list.length) return;
    [list[index], list[target]] = [list[target], list[index]];
    this.items.set(list);
    this.api.reorderTaxonomies(this.kind(), list.map((i) => i.id)).subscribe({
      error: (err) => {
        this.toasts.show(errorMessage(err), 'error');
        this.load();
      },
    });
  }

  protected confirmDelete(): void {
    const item = this.toDelete();
    if (!item) return;
    this.deleting.set(true);
    this.api.deleteTaxonomy(this.kind(), item.id).subscribe({
      next: () => {
        this.deleting.set(false);
        this.toDelete.set(null);
        this.toasts.show(`${this.capitalized()} excluída.`);
        this.load();
      },
      error: (err) => {
        this.deleting.set(false);
        this.toDelete.set(null);
        this.toasts.show(errorMessage(err), 'error');
      },
    });
  }

  protected capitalized(): string {
    return this.noun().charAt(0).toUpperCase() + this.noun().slice(1);
  }
}
