import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, catchError, debounceTime, merge, of, startWith, switchMap, tap } from 'rxjs';
import { ToastService, UiButton, UiFormField, UiInput, UiProgress, UiSkeleton, UiState, UiTranslationBadge } from '@tm/ui';
import { CatalogApi } from '../../core/api/catalog.api';
import { errorMessage } from '../../core/api/errors';
import {
  LOCALE_LABELS,
  Locale,
  TranslatableType,
  TranslationItem,
  TranslationProgress,
  TranslationState,
} from '../../core/api/models';

/** Tela 4.14 — Central de traduções: o que falta revisar em EN e ES. */
@Component({
  selector: 'adm-translations-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, UiButton, UiFormField, UiInput, UiProgress, UiSkeleton, UiState, UiTranslationBadge],
  templateUrl: './translations-page.html',
})
export class TranslationsPage {
  private readonly api = inject(CatalogApi);
  private readonly toasts = inject(ToastService);

  protected readonly targets: Locale[] = ['en', 'es'];
  protected readonly localeLabels = LOCALE_LABELS;
  protected readonly states: { value: TranslationState | ''; label: string }[] = [
    { value: '', label: 'Todos' },
    { value: 'auto', label: 'Automáticas (a revisar)' },
    { value: 'outdated', label: 'Desatualizadas' },
    { value: 'missing', label: 'Ausentes' },
    { value: 'manual', label: 'Revisadas' },
  ];
  protected readonly types: { value: TranslatableType | ''; label: string }[] = [
    { value: '', label: 'Todos' },
    { value: 'artwork', label: 'Obras' },
    { value: 'series', label: 'Séries' },
    { value: 'technique', label: 'Técnicas' },
    { value: 'category', label: 'Categorias' },
  ];

  protected readonly filters = inject(FormBuilder).nonNullable.group({
    locale: ['en' as Locale],
    state: ['' as TranslationState | ''],
    type: ['' as TranslatableType | ''],
    q: [''],
  });

  protected readonly items = signal<TranslationItem[] | null>(null);
  protected readonly error = signal<string | null>(null);
  protected readonly progress = signal<Partial<Record<Locale, TranslationProgress>>>({});
  protected readonly working = signal<string | null>(null);
  private readonly reload$ = new Subject<void>();

  constructor() {
    merge(this.filters.valueChanges.pipe(debounceTime(250)), this.reload$)
      .pipe(
        startWith(null),
        tap(() => {
          this.error.set(null);
          this.loadProgress();
        }),
        switchMap(() =>
          this.api.translations(this.filters.getRawValue()).pipe(
            catchError((err) => {
              this.error.set(errorMessage(err, 'Não foi possível carregar as traduções.'));
              return of(null);
            }),
          ),
        ),
        takeUntilDestroyed(inject(DestroyRef)),
      )
      .subscribe((list) => this.items.set(list ?? []));
  }

  private loadProgress(): void {
    this.api.translationProgress().subscribe({ next: (p) => this.progress.set(p) });
  }

  protected reload(): void {
    this.reload$.next();
  }

  /** Onde revisar cada tipo. */
  protected reviewLink(item: TranslationItem): { path: unknown[]; query: Record<string, string> } {
    switch (item.type) {
      case 'artwork':
        return { path: ['/obras', item.id], query: { idioma: item.locale } };
      case 'series':
        return { path: ['/series'], query: {} };
      case 'technique':
        return { path: ['/tecnicas'], query: {} };
      default:
        return { path: ['/categorias'], query: {} };
    }
  }

  protected generate(item: TranslationItem): void {
    const key = `${item.type}-${item.id}-${item.locale}`;
    this.working.set(key);
    this.api.autoTranslate(item.type, item.id, item.locale).subscribe({
      next: () => {
        this.working.set(null);
        this.toasts.show(`Tradução automática de “${item.title}” gerada. Revise quando puder.`);
        this.reload();
      },
      error: (err) => {
        this.working.set(null);
        this.toasts.show(errorMessage(err), 'error');
      },
    });
  }

  protected isWorking(item: TranslationItem): boolean {
    return this.working() === `${item.type}-${item.id}-${item.locale}`;
  }
}
