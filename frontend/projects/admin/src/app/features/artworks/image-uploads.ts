import { HttpEventType } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Subscription, filter, interval, switchMap, take, takeWhile } from 'rxjs';
import { CatalogApi } from '../../core/api/catalog.api';
import { errorMessage } from '../../core/api/errors';
import { Media } from '../../core/api/models';

export const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/tiff'];
export const MAX_UPLOAD_MB = 30; // P-30

export interface EditorImage {
  key: string;
  fileName: string;
  state: 'uploading' | 'processing' | 'ready' | 'failed';
  progress: number;
  error: string | null;
  media: Media | null;
  isPrimary: boolean;
}

/**
 * Lista de imagens do editor de obra: envio com progresso (um arquivo por requisição),
 * espera do processamento das versões, ordem e imagem principal.
 */
@Injectable()
export class ImageUploads {
  private readonly api = inject(CatalogApi);
  private readonly subs = new Map<string, Subscription>();
  private counter = 0;

  readonly items = signal<EditorImage[]>([]);
  /** Muda quando a lista muda (para "alterações não salvas"). */
  readonly changed = signal(false);

  load(images: { media: Media; is_primary: boolean }[]): void {
    this.items.set(
      images.map((img) => ({
        key: `m${img.media.id}`,
        fileName: img.media.original_filename,
        state: img.media.status === 'failed' ? 'failed' : img.media.status === 'ready' ? 'ready' : 'processing',
        progress: 100,
        error: img.media.error,
        media: img.media,
        isPrimary: img.is_primary,
      })),
    );
    this.changed.set(false);
    this.items().filter((i) => i.state === 'processing').forEach((i) => this.poll(i.key, i.media!.id));
  }

  /** Valida no navegador (tipo e tamanho) e envia cada arquivo. */
  add(files: FileList | File[]): string[] {
    const problems: string[] = [];
    for (const file of Array.from(files)) {
      if (!ACCEPTED_TYPES.includes(file.type)) {
        problems.push(`${file.name}: formato não aceito (use JPG, PNG, TIFF ou WebP).`);
        continue;
      }
      if (file.size > MAX_UPLOAD_MB * 1024 * 1024) {
        problems.push(`${file.name}: passa do limite de ${MAX_UPLOAD_MB} MB.`);
        continue;
      }
      const key = `u${++this.counter}`;
      const isFirst = this.items().length === 0;
      this.items.update((list) => [
        ...list,
        { key, fileName: file.name, state: 'uploading', progress: 0, error: null, media: null, isPrimary: isFirst },
      ]);
      this.changed.set(true);
      this.upload(key, file);
    }
    return problems;
  }

  retry(key: string): void {
    // Sem o arquivo em memória após falha de envio: a pessoa seleciona de novo
    this.remove(key);
  }

  remove(key: string): void {
    this.subs.get(key)?.unsubscribe();
    this.subs.delete(key);
    const wasPrimary = this.items().find((i) => i.key === key)?.isPrimary;
    this.items.update((list) => {
      const next = list.filter((i) => i.key !== key);
      if (wasPrimary && next.length) next[0] = { ...next[0], isPrimary: true };
      return next;
    });
    this.changed.set(true);
  }

  move(index: number, delta: -1 | 1): void {
    const list = [...this.items()];
    const target = index + delta;
    if (target < 0 || target >= list.length) return;
    [list[index], list[target]] = [list[target], list[index]];
    this.items.set(list);
    this.changed.set(true);
  }

  setPrimary(key: string): void {
    this.items.update((list) => list.map((i) => ({ ...i, isPrimary: i.key === key })));
    this.changed.set(true);
  }

  busy(): boolean {
    return this.items().some((i) => i.state === 'uploading' || i.state === 'processing');
  }

  /** Imagens prontas na ordem atual, para salvar a obra. */
  payload(): { media_file_id: number; is_primary: boolean }[] {
    return this.items()
      .filter((i) => i.media && i.state !== 'failed')
      .map((i) => ({ media_file_id: i.media!.id, is_primary: i.isPrimary }));
  }

  destroy(): void {
    this.subs.forEach((s) => s.unsubscribe());
    this.subs.clear();
  }

  private upload(key: string, file: File): void {
    const sub = this.api.upload(file).subscribe({
      next: (event) => {
        if (event.type === HttpEventType.UploadProgress && event.total) {
          this.patch(key, { progress: Math.round((event.loaded / event.total) * 100) });
        } else if (event.type === HttpEventType.Response && event.body) {
          const media = event.body.data;
          this.patch(key, { media, progress: 100, state: media.status === 'ready' ? 'ready' : 'processing' });
          if (media.status !== 'ready') this.poll(key, media.id);
        }
      },
      error: (err) => this.patch(key, { state: 'failed', error: errorMessage(err, 'Falha no envio.') }),
    });
    this.subs.set(key, sub);
  }

  /** Consulta o processamento a cada 1,5 s (até 2 minutos). */
  private poll(key: string, mediaId: number): void {
    const sub = interval(1500)
      .pipe(
        take(80),
        switchMap(() => this.api.media(mediaId)),
        takeWhile((m) => m.status === 'pending' || m.status === 'processing', true),
        filter((m) => m.status === 'ready' || m.status === 'failed'),
      )
      .subscribe({
        next: (media) =>
          this.patch(key, {
            media,
            state: media.status === 'ready' ? 'ready' : 'failed',
            error: media.status === 'failed' ? 'Não foi possível processar esta imagem.' : null,
          }),
        error: (err) => this.patch(key, { state: 'failed', error: errorMessage(err) }),
      });
    this.subs.set(key, sub);
  }

  private patch(key: string, changes: Partial<EditorImage>): void {
    this.items.update((list) => list.map((i) => (i.key === key ? { ...i, ...changes } : i)));
  }
}
