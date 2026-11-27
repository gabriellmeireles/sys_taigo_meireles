import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject, debounceTime, distinctUntilChanged, merge, switchMap, tap, catchError, of } from 'rxjs';
import {
  ToastService,
  UiButton,
  UiFormField,
  UiInput,
  UiSkeleton,
  UiState,
  UiTranslationBadge,
} from '@tm/ui';
import { CatalogApi } from '../../core/api/catalog.api';
import { errorMessage } from '../../core/api/errors';
import { ArtworkListItem, ArtworkStatus, Page, STATUS_LABELS, Taxonomy } from '../../core/api/models';

/** Tela 4.5 — Lista de obras: busca, filtros, status editável na linha, traduções. */
@Component({
  selector: 'adm-artwork-list-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [ReactiveFormsModule, RouterLink, UiButton, UiFormField, UiInput, UiSkeleton, UiState, UiTranslationBadge],
  templateUrl: './artwork-list-page.html',
})
export class ArtworkListPage {
  private readonly api = inject(CatalogApi);
  private readonly toasts = inject(ToastService);

  protected readonly statusLabels = STATUS_LABELS;
  protected readonly statuses = Object.keys(STATUS_LABELS) as ArtworkStatus[];

  protected readonly filters = inject(FormBuilder).nonNullable.group({
    q: [''],
    status: ['' as ArtworkStatus | ''],
    series_id: ['' as number | ''],
  });
  protected readonly page = signal(1);
  protected readonly result = signal<Page<ArtworkListItem> | null>(null);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly series = signal<Taxonomy[]>([]);
  private readonly reload$ = new Subject<void>();

  constructor() {
    this.api.taxonomies('series').subscribe({ next: (s) => this.series.set(s) });

    const filterChanges$ = this.filters.valueChanges.pipe(
      debounceTime(300),
      distinctUntilChanged((a, b) => JSON.stringify(a) === JSON.stringify(b)),
      tap(() => this.page.set(1)),
    );

    merge(of(null), filterChanges$, this.reload$)
      .pipe(
        tap(() => {
          this.loading.set(true);
          this.error.set(null);
        }),
        switchMap(() =>
          this.api.artworks({ ...this.filters.getRawValue(), page: this.page(), per_page: 25 }).pipe(
            catchError((err) => {
              this.error.set(errorMessage(err, 'Não foi possível carregar as obras.'));
              return of(null);
            }),
          ),
        ),
        takeUntilDestroyed(inject(DestroyRef)),
      )
      .subscribe((res) => {
        this.loading.set(false);
        if (res) this.result.set(res);
      });
  }

  protected reload(): void {
    this.reload$.next();
  }

  protected goTo(page: number): void {
    this.page.set(page);
    this.reload();
  }

  protected clearFilters(): void {
    this.filters.reset({ q: '', status: '', series_id: '' });
  }

  protected hasFilters(): boolean {
    const f = this.filters.getRawValue();
    return !!(f.q || f.status || f.series_id);
  }

  protected changeStatus(item: ArtworkListItem, status: ArtworkStatus): void {
    const previous = item.status;
    this.patchItem(item.id, status);
    this.api.setStatus(item.id, status).subscribe({
      next: () => this.toasts.show(`“${item.title}” agora está ${STATUS_LABELS[status].toLowerCase()}.`),
      error: (err) => {
        this.patchItem(item.id, previous);
        this.toasts.show(errorMessage(err), 'error');
      },
    });
  }

  private patchItem(id: number, status: ArtworkStatus): void {
    const r = this.result();
    if (!r) return;
    this.result.set({ ...r, data: r.data.map((i) => (i.id === id ? { ...i, status } : i)) });
  }

  protected price(item: ArtworkListItem): string {
    if (!item.show_price) return 'oculto';
    const brl = item.price_brl !== null ? `R$ ${item.price_brl.toLocaleString('pt-BR')}` : '—';
    const usd = item.price_usd !== null ? `US$ ${item.price_usd.toLocaleString('en-US')}` : '—';
    return `${brl} · ${usd}`;
  }
}
