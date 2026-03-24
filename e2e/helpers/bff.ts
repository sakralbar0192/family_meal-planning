import type { APIRequestContext } from '@playwright/test';

export function bffBaseUrl(): string {
  return (process.env.E2E_BFF_BASE_URL ?? 'http://localhost:8080/bff/v1').replace(/\/$/, '');
}

export async function isBffHealthy(request: APIRequestContext): Promise<boolean> {
  const base = bffBaseUrl();
  try {
    const res = await request.get(`${base}/health`, { timeout: 5000 });
    return res.status() === 200;
  } catch {
    return false;
  }
}

export function requiresBff(): boolean {
  return process.env.E2E_FULL_STACK === '1' || process.env.E2E_REQUIRE_BFF === '1';
}
