/**
 * HTTP-клиент к BFF (`/bff/v1`). Всегда `credentials: 'include'` для cookie сессии
 * (см. contracts/bff-routes.md, contracts/openapi/bff.openapi.yaml).
 */

import { bffErrorFromResponse } from './errors';

export * from './bff-types';
export * from './errors';
export * from './default-client';

export type BffClient = {
  fetch(path: string, init?: RequestInit): Promise<Response>;
  json<T>(path: string, init?: RequestInit): Promise<T>;
};

const DEFAULT_BASE = 'http://localhost:8080/bff/v1';

function normalizeBase(base: string): string {
  return base.replace(/\/$/, '');
}

function mergeHeaders(init?: RequestInit): Headers {
  const h = new Headers(init?.headers);
  if (!h.has('X-Correlation-Id') && typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    h.set('X-Correlation-Id', crypto.randomUUID());
  }
  const body = init?.body;
  if (
    body !== undefined &&
    body !== null &&
    !(body instanceof FormData) &&
    !(body instanceof Blob) &&
    !h.has('Content-Type')
  ) {
    h.set('Content-Type', 'application/json');
  }
  return h;
}

/** Базовый URL BFF из `import.meta.env.VITE_BFF_BASE_URL` или дефолт для локальной разработки. */
export function resolveBffBaseUrl(envValue?: string): string {
  if (typeof envValue === 'string' && envValue !== '') {
    return normalizeBase(envValue);
  }
  return DEFAULT_BASE;
}

/** Собрать path с query для BFF (значения пустые пропускаются). */
export function bffPath(path: string, query?: Record<string, string | number | undefined>): string {
  if (!query) {
    return path.startsWith('/') ? path : `/${path}`;
  }
  const u = new URLSearchParams();
  for (const [k, v] of Object.entries(query)) {
    if (v !== undefined && v !== '') {
      u.set(k, String(v));
    }
  }
  const q = u.toString();
  const p = path.startsWith('/') ? path : `/${path}`;
  return q ? `${p}?${q}` : p;
}

export function createBffClient(baseUrl: string = DEFAULT_BASE): BffClient {
  const root = normalizeBase(baseUrl);

  async function bffFetch(path: string, init?: RequestInit): Promise<Response> {
    const p = path.startsWith('/') ? path : `/${path}`;
    return fetch(`${root}${p}`, {
      ...init,
      credentials: 'include',
      headers: mergeHeaders(init),
    });
  }

  return {
    fetch: bffFetch,
    async json<T>(path: string, init?: RequestInit): Promise<T> {
      const res = await bffFetch(path, init);
      if (!res.ok) {
        throw await bffErrorFromResponse(res);
      }
      if (res.status === 204) {
        return undefined as T;
      }
      const ct = res.headers.get('Content-Type');
      if (ct?.includes('application/json')) {
        return (await res.json()) as T;
      }
      return undefined as T;
    },
  };
}
