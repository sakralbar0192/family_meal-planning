import { createBffClient, resolveBffBaseUrl, type BffClient } from '@meal/bff-client';

export function useBff(): BffClient {
  return createBffClient(resolveBffBaseUrl(import.meta.env.VITE_BFF_BASE_URL));
}
