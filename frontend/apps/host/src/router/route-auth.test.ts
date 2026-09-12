import { describe, expect, it } from 'vitest';
import type { RouteLocationNormalized } from 'vue-router';
import { APP_ROUTES } from '../app/routes';
import { routeRequiresAuth } from './route-guard';

function mkRoute(meta: Record<string, unknown>): RouteLocationNormalized {
  return {
    meta,
  } as RouteLocationNormalized;
}

describe('APP_ROUTES', () => {
  it('defines public auth paths used by the router', () => {
    expect(APP_ROUTES.LOGIN).toBe('/login');
    expect(APP_ROUTES.REGISTER).toBe('/register');
  });
});

describe('routeRequiresAuth', () => {
  it('returns true for protected routes', () => {
    expect(routeRequiresAuth(mkRoute({ requiresAuth: true }))).toBe(true);
  });

  it('returns false for public routes', () => {
    expect(routeRequiresAuth(mkRoute({}))).toBe(false);
  });
});
