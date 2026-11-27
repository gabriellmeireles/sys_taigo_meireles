import { InjectionToken } from '@angular/core';
import { environment } from '../../../environments/environment';

/** Endereço da API Laravel (sem barra final). */
export const API_BASE_URL = new InjectionToken<string>('API_BASE_URL', {
  providedIn: 'root',
  factory: () => environment.apiBaseUrl,
});

export const SITE_URL = new InjectionToken<string>('SITE_URL', {
  providedIn: 'root',
  factory: () => environment.siteUrl,
});
