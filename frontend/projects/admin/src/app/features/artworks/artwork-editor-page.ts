import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  HostListener,
  computed,
  effect,
  inject,
  input,
  signal,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import {
  ToastService,
  UiButton,
  UiConfirmDialog,
  UiFormField,
  UiInput,
  UiProgress,
  UiSkeleton,
  UiState,
  UiTranslationBadge,
} from '@tm/ui';
import { SITE_URL } from '../../core/api/api-config';
import { CatalogApi } from '../../core/api/catalog.api';
import { errorMessage, fieldErrors } from '../../core/api/errors';
import {
  Artwork,
  ArtworkPayload,
  ArtworkStatus,
  FieldErrors,
  LOCALES,
  LOCALE_LABELS,
  Locale,
  STATUS_LABELS,
  Taxonomy,
  TranslationState,
} from '../../core/api/models';
import { HasUnsavedChanges } from '../../core/auth/guards';
import { ACCEPTED_TYPES, ImageUploads, MAX_UPLOAD_MB } from './image-uploads';

type TranslationForm = FormGroup<{
  title: import('@angular/forms').FormControl<string>;
  slug: import('@angular/forms').FormControl<string>;
  description: import('@angular/forms').FormControl<string>;
  dimensions_note: import('@angular/forms').FormControl<string>;
  seo_title: import('@angular/forms').FormControl<string>;
  seo_description: import('@angular/forms').FormControl<string>;
}>;

/** Tela 4.6 — Editor de obra. Salvar publica (sem rascunho). */
@Component({
  selector: 'adm-artwork-editor-page',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    ReactiveFormsModule, RouterLink, UiButton, UiConfirmDialog, UiFormField, UiInput, UiProgress,
    UiSkeleton, UiState, UiTranslationBadge,
  ],
  providers: [ImageUploads],
  templateUrl: './artwork-editor-page.html',
})
export class ArtworkEditorPage implements HasUnsavedChanges {
  private readonly api = inject(CatalogApi);
  private readonly toasts = inject(ToastService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder).nonNullable;
  protected readonly images = inject(ImageUploads);
  protected readonly siteUrl = inject(SITE_URL);

  /** Parâmetro da rota: id da obra (ausente em "Nova obra"). */
  readonly id = input<string | undefined>();
  protected readonly artworkId = computed(() => (this.id() ? Number(this.id()) : null));

  protected readonly locales = LOCALES;
  protected readonly localeLabels = LOCALE_LABELS;
  protected readonly statusLabels = STATUS_LABELS;
  protected readonly statuses = Object.keys(STATUS_LABELS) as ArtworkStatus[];
  protected readonly accept = ACCEPTED_TYPES.join(',');
  protected readonly maxMb = MAX_UPLOAD_MB;

  protected readonly form = this.fb.group({
    year: [new Date().getFullYear() as number | null],
    height_cm: [null as number | null],
    width_cm: [null as number | null],
    depth_cm: [null as number | null],
    category_id: [null as number | null],
    technique_id: [null as number | null],
    series_id: [null as number | null],
    status: ['available' as ArtworkStatus],
    show_price: [false],
    price_brl: [null as number | null],
    price_usd: [null as number | null],
    video_url: [''],
    translations: this.fb.group(
      Object.fromEntries(LOCALES.map((l) => [l, this.translationGroup()])) as Record<Locale, TranslationForm>,
    ),
  });

  protected readonly locale = signal<Locale>('pt-BR');
  /** Grupo de textos do idioma em edição (trocar o idioma troca o grupo ligado aos campos). */
  protected readonly tGroup = computed(() => this.form.controls.translations.controls[this.locale()]);
  protected readonly loading = signal(true);
  protected readonly loadError = signal<string | null>(null);
  protected readonly saving = signal(false);
  protected readonly errors = signal<FieldErrors>({});
  protected readonly uploadProblems = signal<string[]>([]);
  protected readonly confirmDelete = signal(false);
  protected readonly deleting = signal(false);
  protected readonly translationStates = signal<Record<Locale, TranslationState> | null>(null);
  protected readonly dragging = signal(false);

  protected readonly categories = signal<Taxonomy[]>([]);
  protected readonly techniques = signal<Taxonomy[]>([]);
  protected readonly series = signal<Taxonomy[]>([]);

  private readonly formValue = toSignal(this.form.valueChanges, { initialValue: this.form.getRawValue() });
  protected readonly showPrice = computed(() => !!this.formValue().show_price);
  protected readonly currentTranslation = computed(() => this.formValue().translations?.[this.locale()] ?? {});
  protected readonly sourceTranslation = computed(() => this.formValue().translations?.['pt-BR'] ?? {});
  protected readonly priceWarning = computed(() => {
    const v = this.formValue();
    if (!v.show_price) return null;
    if (v.status !== 'available') return 'Obras reservadas e vendidas não mostram preço no site (P-05).';
    return null;
  });

  constructor() {
    inject(DestroyRef).onDestroy(() => this.images.destroy());

    effect(() => {
      const id = this.artworkId();
      this.load(id);
    });
  }

  private translationGroup(): TranslationForm {
    return this.fb.group({
      title: [''],
      slug: [''],
      description: [''],
      dimensions_note: [''],
      seo_title: [''],
      seo_description: [''],
    });
  }

  private load(id: number | null): void {
    this.loading.set(true);
    this.loadError.set(null);
    const lists = forkJoin({
      categories: this.api.taxonomies('categories'),
      techniques: this.api.taxonomies('techniques'),
      series: this.api.taxonomies('series'),
    });

    lists.subscribe({
      next: (l) => {
        this.categories.set(l.categories);
        this.techniques.set(l.techniques);
        this.series.set(l.series);
        if (!id) {
          // Nova obra: pré-seleciona quando só existe uma opção
          this.form.patchValue({
            category_id: l.categories.length === 1 ? l.categories[0].id : null,
            technique_id: l.techniques.length === 1 ? l.techniques[0].id : null,
          });
          this.form.markAsPristine();
          this.images.load([]);
          this.loading.set(false);
          return;
        }
        this.api.artwork(id).subscribe({
          next: (a) => this.fill(a),
          error: (err) => {
            this.loading.set(false);
            this.loadError.set(errorMessage(err, 'Não foi possível carregar a obra.'));
          },
        });
      },
      error: (err) => {
        this.loading.set(false);
        this.loadError.set(errorMessage(err, 'Não foi possível carregar os dados.'));
      },
    });
  }

  private fill(a: Artwork): void {
    const translations = Object.fromEntries(
      LOCALES.map((l) => {
        const t = a.translations[l];
        return [l, {
          title: t?.title ?? '', slug: t?.slug ?? '', description: t?.description ?? '',
          dimensions_note: t?.dimensions_note ?? '', seo_title: t?.seo_title ?? '', seo_description: t?.seo_description ?? '',
        }];
      }),
    );
    this.form.reset({
      year: a.year, height_cm: a.height_cm, width_cm: a.width_cm, depth_cm: a.depth_cm,
      category_id: a.category_id, technique_id: a.technique_id, series_id: a.series_id,
      status: a.status, show_price: a.show_price, price_brl: a.price_brl, price_usd: a.price_usd,
      video_url: a.video_url ?? '', translations: translations as never,
    });
    this.translationStates.set(a.translation_states);
    this.images.load(a.images);
    this.loading.set(false);
  }

  // ---------- Imagens ----------
  protected onFiles(files: FileList | null): void {
    if (!files?.length) return;
    this.uploadProblems.set(this.images.add(files));
  }

  protected onDrop(event: DragEvent): void {
    event.preventDefault();
    this.dragging.set(false);
    this.onFiles(event.dataTransfer?.files ?? null);
  }

  // ---------- Salvar ----------
  protected err(field: string): string | null {
    return this.errors()[field]?.[0] ?? null;
  }

  protected save(): void {
    if (this.images.busy()) {
      this.toasts.show('Aguarde o envio e o processamento das imagens antes de salvar.', 'info');
      return;
    }
    const v = this.form.getRawValue();
    const translations = Object.fromEntries(
      // Português sempre vai (mesmo vazio, para a validação apontar o título); os outros só com título
      LOCALES.filter((l) => l === 'pt-BR' || v.translations[l].title.trim()).map((l) => [l, {
        ...v.translations[l],
        slug: v.translations[l].slug.trim() || null,
      }]),
    );
    const payload: ArtworkPayload = {
      year: v.year, height_cm: v.height_cm, width_cm: v.width_cm, depth_cm: v.depth_cm,
      category_id: v.category_id, technique_id: v.technique_id, series_id: v.series_id,
      status: v.status, show_price: v.show_price, price_brl: v.price_brl, price_usd: v.price_usd,
      video_url: v.video_url.trim() || null,
      images: this.images.payload(),
      translations,
    };

    this.saving.set(true);
    this.errors.set({});
    this.toasts.clear();
    const id = this.artworkId();
    this.api.saveArtwork(id, payload).subscribe({
      next: (artwork) => {
        this.saving.set(false);
        this.toasts.show('Obra salva e publicada.');
        if (!id) {
          this.form.markAsPristine();
          this.images.changed.set(false);
          this.router.navigate(['/obras', artwork.id], { replaceUrl: true });
        } else {
          this.fill(artwork);
        }
      },
      error: (err) => {
        this.saving.set(false);
        const fields = fieldErrors(err);
        this.errors.set(fields);
        const count = Object.keys(fields).length;
        this.toasts.show(
          count ? `Revise ${count === 1 ? 'o campo destacado' : `os ${count} campos destacados`}.` : errorMessage(err),
          'error',
        );
        // Erro em tradução de outro idioma: mostra esse idioma
        const translationKey = Object.keys(fields).find((k) => k.startsWith('translations.'));
        if (translationKey) this.locale.set(translationKey.split('.')[1] as Locale);
        setTimeout(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus());
      },
    });
  }

  protected remove(): void {
    const id = this.artworkId();
    if (!id) return;
    this.deleting.set(true);
    this.api.deleteArtwork(id).subscribe({
      next: () => {
        this.form.markAsPristine();
        this.images.changed.set(false);
        this.toasts.show('Obra excluída.');
        this.router.navigate(['/obras']);
      },
      error: (err) => {
        this.deleting.set(false);
        this.confirmDelete.set(false);
        this.toasts.show(errorMessage(err), 'error');
      },
    });
  }

  hasUnsavedChanges(): boolean {
    return (this.form.dirty || this.images.changed()) && !this.saving();
  }

  @HostListener('window:beforeunload', ['$event'])
  protected beforeUnload(event: BeforeUnloadEvent): void {
    if (this.hasUnsavedChanges()) event.preventDefault();
  }

  protected slugPreview(): string {
    const t = this.currentTranslation();
    const prefix = { 'pt-BR': '/pt/obras/', en: '/en/works/', es: '/es/obras/' }[this.locale()];
    const slug = (t.slug || t.title || 'titulo-da-obra')
      .toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    return prefix + slug;
  }
}
