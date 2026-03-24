import { getDefaultBffClient, type BffClient } from '@meal/bff-client';

export function useBff(): BffClient {
  return getDefaultBffClient(import.meta.env.VITE_BFF_BASE_URL);
}
