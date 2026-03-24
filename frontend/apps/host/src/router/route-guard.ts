import type { RouteLocationNormalized } from 'vue-router';

export function routeRequiresAuth(to: RouteLocationNormalized): boolean {
  return Boolean(to.meta.requiresAuth);
}
