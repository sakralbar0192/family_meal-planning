import { createBffClient, resolveBffBaseUrl, type BffClient } from './index';

let defaultClient: BffClient | null = null;

export function getDefaultBffClient(envValue?: string): BffClient {
  if (!defaultClient) {
    defaultClient = createBffClient(resolveBffBaseUrl(envValue));
  }
  return defaultClient;
}
