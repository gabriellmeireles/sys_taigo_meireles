/** Contratos da API do painel (backend/app/Http/Resources). */

export type Locale = 'pt-BR' | 'en' | 'es';
export const LOCALES: Locale[] = ['pt-BR', 'en', 'es'];
export const LOCALE_LABELS: Record<Locale, string> = { 'pt-BR': 'PT', en: 'EN', es: 'ES' };

export type TranslationState = 'manual' | 'auto' | 'outdated' | 'missing';
export type ArtworkStatus = 'available' | 'reserved' | 'sold';
export const STATUS_LABELS: Record<ArtworkStatus, string> = {
  available: 'Disponível',
  reserved: 'Reservada',
  sold: 'Vendida',
};

export interface Admin {
  id: number;
  name: string;
  email: string;
  status: 'active' | 'invited' | 'inactive';
  is_current: boolean;
  last_login_at: string | null;
}

export interface Page<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export type TaxonomyKind = 'categories' | 'techniques' | 'series';

export interface Taxonomy {
  id: number;
  name: string;
  sort_order: number;
  artworks_count: number | null;
  translations: Partial<Record<Locale, { name: string; slug: string; translation_status: string }>>;
  translation_states: Record<Locale, TranslationState>;
}

export interface MediaVariant {
  variant: string;
  format: 'webp' | 'jpeg' | 'avif';
  width: number;
  height: number;
  size_bytes: number;
  url: string;
}

export interface Media {
  id: number;
  status: 'pending' | 'processing' | 'ready' | 'failed';
  error: string | null;
  original_filename: string;
  size_bytes: number;
  width: number | null;
  height: number | null;
  dominant_color: string | null;
  preview_url: string | null;
  original_url: string;
  variants: MediaVariant[];
}

export interface ArtworkListItem {
  id: number;
  title: string;
  year: number;
  status: ArtworkStatus;
  series: string | null;
  technique: string | null;
  show_price: boolean;
  price_brl: number | null;
  price_usd: number | null;
  thumbnail_url: string | null;
  thumbnail_color: string | null;
  translation_states: Record<Locale, TranslationState>;
  updated_at: string;
}

export interface ArtworkTranslation {
  title: string;
  slug?: string | null;
  description?: string | null;
  dimensions_note?: string | null;
  seo_title?: string | null;
  seo_description?: string | null;
  og_title?: string | null;
  og_description?: string | null;
  translation_status?: string;
  is_outdated?: boolean;
}

export interface ArtworkImage {
  id?: number;
  media_file_id: number;
  is_primary: boolean;
  media: Media;
}

export interface Artwork {
  id: number;
  title: string;
  year: number;
  height_cm: number | null;
  width_cm: number | null;
  depth_cm: number | null;
  category_id: number;
  technique_id: number;
  series_id: number;
  status: ArtworkStatus;
  show_price: boolean;
  price_brl: number | null;
  price_usd: number | null;
  video_url: string | null;
  translations: Partial<Record<Locale, ArtworkTranslation>>;
  translation_states: Record<Locale, TranslationState>;
  images: ArtworkImage[];
  updated_at: string;
}

export interface ArtworkPayload {
  year: number | null;
  height_cm: number | null;
  width_cm: number | null;
  depth_cm: number | null;
  category_id: number | null;
  technique_id: number | null;
  series_id: number | null;
  status: ArtworkStatus;
  show_price: boolean;
  price_brl: number | null;
  price_usd: number | null;
  video_url: string | null;
  images: { media_file_id: number; is_primary: boolean }[];
  translations: Partial<Record<Locale, ArtworkTranslation>>;
}

/** Erros de validação do Laravel (422): { campo: [mensagens] }. */
export type FieldErrors = Record<string, string[]>;
