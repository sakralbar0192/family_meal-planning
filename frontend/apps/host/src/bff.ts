import { getDefaultBffClient, type BffClient } from '@meal/bff-client';

let client: BffClient | null = null;

export function getBff(): BffClient {
  if (!client) {
    client = getDefaultBffClient(import.meta.env.VITE_BFF_BASE_URL);
  }
  return client;
}
