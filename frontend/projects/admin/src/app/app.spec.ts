import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { Router, provideRouter } from '@angular/router';
import { App } from './app';
import { API_BASE_URL } from './core/api/api-config';
import { apiInterceptor, readCookie } from './core/api/api.interceptor';
import { errorMessage } from './core/api/errors';
import { SessionState } from './core/auth/session-state';
import { ImageUploads } from './features/artworks/image-uploads';
import { HttpErrorResponse } from '@angular/common/http';

const API = 'http://api.test';

describe('App (painel)', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [App],
      providers: [
        provideRouter([]),
        provideHttpClient(withInterceptors([apiInterceptor])),
        provideHttpClientTesting(),
        { provide: API_BASE_URL, useValue: API },
      ],
    });
  });

  it('tem a saída de rotas e de avisos', async () => {
    const fixture = TestBed.createComponent(App);
    await fixture.whenStable();
    const el = fixture.nativeElement as HTMLElement;
    expect(el.querySelector('router-outlet')).toBeTruthy();
    expect(el.querySelector('ui-toast-outlet')).toBeTruthy();
  });

  it('lê o cookie XSRF-TOKEN decodificado', () => {
    expect(readCookie('XSRF-TOKEN', 'a=1; XSRF-TOKEN=abc%3D%3D; b=2')).toBe('abc==');
    expect(readCookie('XSRF-TOKEN', 'a=1')).toBeNull();
  });

  it('envia credenciais e o cabeçalho CSRF só para a API', () => {
    document.cookie = 'XSRF-TOKEN=token%3D123; path=/';
    const http = TestBed.inject(HttpClient);
    const ctrl = TestBed.inject(HttpTestingController);

    http.get(`${API}/api/v1/admin/artworks`).subscribe();
    const apiReq = ctrl.expectOne(`${API}/api/v1/admin/artworks`);
    expect(apiReq.request.withCredentials).toBe(true);
    expect(apiReq.request.headers.get('X-XSRF-TOKEN')).toBe('token=123');
    expect(apiReq.request.headers.get('Accept')).toBe('application/json');
    apiReq.flush({});

    http.get('https://outro.test/x').subscribe();
    const other = ctrl.expectOne('https://outro.test/x');
    expect(other.request.withCredentials).toBe(false);
    expect(other.request.headers.has('X-XSRF-TOKEN')).toBe(false);
    other.flush({});
  });

  it('sessão expirada fora do login volta para /entrar guardando o destino', () => {
    const http = TestBed.inject(HttpClient);
    const ctrl = TestBed.inject(HttpTestingController);
    const router = TestBed.inject(Router);
    const session = TestBed.inject(SessionState);
    session.set({ id: 1, name: 'A', email: 'a@a', status: 'active', is_current: true, last_login_at: null });
    const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

    http.get(`${API}/api/v1/admin/artworks`).subscribe({ error: () => undefined });
    ctrl.expectOne(`${API}/api/v1/admin/artworks`).flush({}, { status: 401, statusText: 'Unauthorized' });

    expect(session.admin()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/entrar'], expect.objectContaining({ queryParams: expect.objectContaining({ expirada: 1 }) }));

    navigate.mockClear();
    http.get(`${API}/api/v1/admin/auth/me`).subscribe({ error: () => undefined });
    ctrl.expectOne(`${API}/api/v1/admin/auth/me`).flush({}, { status: 401, statusText: 'Unauthorized' });
    expect(navigate).not.toHaveBeenCalled();
  });

  it('traduz erros da API em mensagens legíveis', () => {
    const validation = new HttpErrorResponse({ status: 422, error: { errors: { year: ['Informe o ano.'] } } });
    expect(errorMessage(validation)).toBe('Informe o ano.');
    expect(errorMessage(new HttpErrorResponse({ status: 0 }))).toContain('Sem conexão');
    expect(errorMessage(new HttpErrorResponse({ status: 500 }))).toContain('erro');
  });
});

describe('ImageUploads', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [ImageUploads, provideHttpClient(), provideHttpClientTesting(), { provide: API_BASE_URL, useValue: API }],
    });
  });

  it('recusa formato e tamanho inválidos antes de enviar', () => {
    const uploads = TestBed.inject(ImageUploads);
    const pdf = new File(['x'], 'doc.pdf', { type: 'application/pdf' });
    const big = new File(['x'], 'grande.jpg', { type: 'image/jpeg' });
    Object.defineProperty(big, 'size', { value: 31 * 1024 * 1024 });

    const problems = uploads.add([pdf, big]);

    expect(problems).toHaveLength(2);
    expect(problems[0]).toContain('formato não aceito');
    expect(problems[1]).toContain('30 MB');
    expect(uploads.items()).toHaveLength(0);
  });

  it('envia, marca a primeira como principal, reordena e troca a principal', () => {
    const uploads = TestBed.inject(ImageUploads);
    const ctrl = TestBed.inject(HttpTestingController);
    const media = (id: number) => ({ id, status: 'ready', error: null, original_filename: `${id}.jpg`, size_bytes: 1, width: 10, height: 10, dominant_color: null, preview_url: null, original_url: '', variants: [] });

    uploads.add([new File(['a'], 'a.jpg', { type: 'image/jpeg' }), new File(['b'], 'b.jpg', { type: 'image/jpeg' })]);
    const reqs = ctrl.match(`${API}/api/v1/admin/media`);
    expect(reqs).toHaveLength(2);
    reqs[0].flush({ data: media(10) });
    reqs[1].flush({ data: media(20) });

    expect(uploads.busy()).toBe(false);
    expect(uploads.payload()).toEqual([
      { media_file_id: 10, is_primary: true },
      { media_file_id: 20, is_primary: false },
    ]);

    uploads.move(1, -1);
    uploads.setPrimary(uploads.items()[0].key);
    expect(uploads.payload()).toEqual([
      { media_file_id: 20, is_primary: true },
      { media_file_id: 10, is_primary: false },
    ]);

    uploads.remove(uploads.items()[0].key);
    expect(uploads.payload()).toEqual([{ media_file_id: 10, is_primary: true }]);
  });
});
