import { getDefaultBffClient, type BffClient } from '@meal/bff-client';

let client: BffClient | null = null;

export function getBff(): BffClient {
  if (!client) {
    const base =
      import.meta.env.VITE_DEMO_MODE === '1'
        ? `${import.meta.env.BASE_URL.replace(/\/$/, '')}/bff/v1`
        : import.meta.env.VITE_BFF_BASE_URL;
    client = getDefaultBffClient(base);
  }
  return client;
}
