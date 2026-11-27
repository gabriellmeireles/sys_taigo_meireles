import { HttpClient, HttpEvent, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { API_BASE_URL } from './api-config';
import {
  Artwork,
  ArtworkListItem,
  ArtworkPayload,
  ArtworkStatus,
  Locale,
  Media,
  Page,
  Taxonomy,
  TaxonomyKind,
} from './models';

export interface ArtworkFilters {
  q?: string;
  status?: ArtworkStatus | '';
  series_id?: number | '';
  page?: number;
  per_page?: number;
}

/** Endpoints do catálogo no painel (/api/v1/admin/...). */
@Injectable({ providedIn: 'root' })
export class CatalogApi {
  private readonly http = inject(HttpClient);
  private readonly base = inject(API_BASE_URL);

  private url(path: string): string {
    return `${this.base}/api/v1/admin/${path}`;
  }

  // Taxonomias
  taxonomies(kind: TaxonomyKind): Observable<Taxonomy[]> {
    return this.http.get<{ data: Taxonomy[] }>(this.url(kind)).pipe(map((r) => r.data));
  }

  saveTaxonomy(kind: TaxonomyKind, id: number | null, names: Partial<Record<Locale, string>>): Observable<Taxonomy> {
    const translations = Object.fromEntries(Object.entries(names).map(([l, name]) => [l, { name }]));
    const req = id
      ? this.http.put<{ data: Taxonomy }>(this.url(`${kind}/${id}`), { translations })
      : this.http.post<{ data: Taxonomy }>(this.url(kind), { translations });
    return req.pipe(map((r) => r.data));
  }

  deleteTaxonomy(kind: TaxonomyKind, id: number): Observable<void> {
    return this.http.delete<void>(this.url(`${kind}/${id}`));
  }

  reorderTaxonomies(kind: TaxonomyKind, ids: number[]): Observable<void> {
    return this.http.put<void>(this.url(`${kind}/order`), { ids });
  }

  // Obras
  artworks(filters: ArtworkFilters): Observable<Page<ArtworkListItem>> {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(filters)) {
      if (value !== '' && value !== null && value !== undefined) params = params.set(key, String(value));
    }
    return this.http.get<Page<ArtworkListItem>>(this.url('artworks'), { params });
  }

  artwork(id: number): Observable<Artwork> {
    return this.http.get<{ data: Artwork }>(this.url(`artworks/${id}`)).pipe(map((r) => r.data));
  }

  saveArtwork(id: number | null, payload: ArtworkPayload): Observable<Artwork> {
    const req = id
      ? this.http.put<{ data: Artwork }>(this.url(`artworks/${id}`), payload)
      : this.http.post<{ data: Artwork }>(this.url('artworks'), payload);
    return req.pipe(map((r) => r.data));
  }

  setStatus(id: number, status: ArtworkStatus): Observable<void> {
    return this.http.patch<void>(this.url(`artworks/${id}/status`), { status });
  }

  deleteArtwork(id: number): Observable<void> {
    return this.http.delete<void>(this.url(`artworks/${id}`));
  }

  // Mídia
  /** Upload com eventos de progresso (HttpEventType.UploadProgress / Response). */
  upload(file: File): Observable<HttpEvent<{ data: Media }>> {
    const body = new FormData();
    body.append('file', file);
    return this.http.post<{ data: Media }>(this.url('media'), body, { reportProgress: true, observe: 'events' });
  }

  media(id: number): Observable<Media> {
    return this.http.get<{ data: Media }>(this.url(`media/${id}`)).pipe(map((r) => r.data));
  }
}
