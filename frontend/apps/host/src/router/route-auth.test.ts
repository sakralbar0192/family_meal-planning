import { describe, expect, it } from 'vitest';
import type { RouteLocationNormalized } from 'vue-router';
import { routeRequiresAuth } from './route-guard';

function mkRoute(meta: Record<string, unknown>): RouteLocationNormalized {
  return {
    meta,
  } as RouteLocationNormalized;
}

describe('routeRequiresAuth', () => {
  it('returns true for protected routes', () => {
    expect(routeRequiresAuth(mkRoute({ requiresAuth: true }))).toBe(true);
  });

  it('returns false for public routes', () => {
    expect(routeRequiresAuth(mkRoute({}))).toBe(false);
  });
});
