import { http, HttpResponse } from 'msw';
import type { Recipe } from '@meal/bff-client';
import {
  buildShopping,
  getDemoStore,
  listRecipes,
  weekPlan,
} from './seed';

function matchBff(pathSuffix: string, url: URL): boolean {
  return url.pathname.endsWith(`/bff/v1${pathSuffix}`) || url.pathname.endsWith(pathSuffix);
}

function json(data: unknown, status = 200): Response {
  return HttpResponse.json(data, { status });
}

export const demoHandlers = [
  http.get(({ request }) => {
    const url = new URL(request.url);
    return matchBff('/health', url);
  }, () => json({ status: 'ok' })),

  http.get(({ request }) => {
    const url = new URL(request.url);
    return matchBff('/recipes', url) && !url.pathname.match(/\/recipes\/[^/]+$/);
  }, ({ request }) => {
    const url = new URL(request.url);
    const q = url.searchParams.get('q') ?? undefined;
    return json(listRecipes(q ?? undefined));
  }),

  http.get(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/recipes\/[^/]+$/.test(url.pathname);
  }, ({ request }) => {
    const url = new URL(request.url);
    const id = url.pathname.split('/').pop()!;
    const recipe = getDemoStore().recipes.find((r) => r.id === id);
    if (!recipe) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Not found' }, { status: 404 });
    return json(recipe);
  }),

  http.post(({ request }) => matchBff('/recipes', new URL(request.url)), async ({ request }) => {
    const body = (await request.json()) as Partial<Recipe>;
    const id = crypto.randomUUID();
    const now = new Date().toISOString();
    const recipe: Recipe = {
      id,
      title: body.title ?? 'Без названия',
      steps: body.steps ?? [],
      cookTimeMinutes: body.cookTimeMinutes ?? null,
      mealCategory: body.mealCategory ?? null,
      nutrition: body.nutrition ?? null,
      ingredients: body.ingredients ?? [],
      sourceUrl: body.sourceUrl ?? null,
      note: body.note ?? null,
      imageUrl: body.imageUrl ?? null,
      createdAt: now,
      updatedAt: now,
    };
    getDemoStore().recipes.unshift(recipe);
    return json(recipe, 201);
  }),

  http.patch(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/recipes\/[^/]+$/.test(url.pathname);
  }, async ({ request }) => {
    const url = new URL(request.url);
    const id = url.pathname.split('/').pop()!;
    const body = (await request.json()) as Partial<Recipe>;
    const store = getDemoStore();
    const idx = store.recipes.findIndex((r) => r.id === id);
    if (idx < 0) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Not found' }, { status: 404 });
    store.recipes[idx] = {
      ...store.recipes[idx],
      ...body,
      id,
      updatedAt: new Date().toISOString(),
    } as Recipe;
    return json(store.recipes[idx]);
  }),

  http.delete(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/recipes\/[^/]+$/.test(url.pathname);
  }, ({ request }) => {
    const url = new URL(request.url);
    const id = url.pathname.split('/').pop()!;
    const store = getDemoStore();
    store.recipes = store.recipes.filter((r) => r.id !== id);
    for (const slot of store.slots) {
      slot.recipeIds = slot.recipeIds.filter((rid) => rid !== id);
    }
    return new HttpResponse(null, { status: 204 });
  }),

  http.post(({ request }) => matchBff('/import/url', new URL(request.url)), async ({ request }) => {
    const body = (await request.json()) as { url?: string };
    const url = body.url ?? '';
    const isPov = url.includes('povarenok');
    return json({
      title: isPov ? 'Куриный суп' : 'Борщ классический',
      steps: isPov ? ['Отварить курицу', 'Добавить овощи'] : ['Сварить бульон', 'Добавить овощи'],
      cookTimeMinutes: isPov ? 45 : 90,
      ingredients: isPov
        ? [{ name: 'Куриное филе', quantity: 400, unit: 'г', productCategory: 'meat' }]
        : [{ name: 'Говядина', quantity: 500, unit: 'г', productCategory: 'meat' }],
      sourceUrl: url,
      nutrition: isPov ? null : { proteinG: 12, fatG: 8, carbsG: 15, calories: 180 },
    });
  }),

  http.get(({ request }) => matchBff('/plan/week', new URL(request.url)), ({ request }) => {
    const url = new URL(request.url);
    const anchor = url.searchParams.get('anchorDate') ?? undefined;
    const recipeSearch = url.searchParams.get('recipeSearch') ?? undefined;
    return json(weekPlan(anchor, recipeSearch ?? undefined));
  }),

  http.patch(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/plan\/slots\/[^/]+$/.test(url.pathname);
  }, async ({ request }) => {
    const url = new URL(request.url);
    const slotId = url.pathname.split('/').pop()!;
    const body = (await request.json()) as { recipeIds?: string[]; expectedVersion?: number };
    const slot = getDemoStore().slots.find((s) => s.slotId === slotId);
    if (!slot) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Slot not found' }, { status: 404 });
    if (body.expectedVersion != null && body.expectedVersion !== slot.version) {
      return HttpResponse.json({ code: 'VERSION_CONFLICT', message: 'Version conflict' }, { status: 409 });
    }
    slot.recipeIds = body.recipeIds ?? [];
    slot.version += 1;
    return json({
      slotId: slot.slotId,
      date: slot.date,
      slotCode: slot.slotCode,
      recipeIds: [...slot.recipeIds],
      version: slot.version,
    });
  }),

  http.post(({ request }) => matchBff('/shopping/build', new URL(request.url)), async ({ request }) => {
    const body = (await request.json()) as { from: string; to: string };
    return json(buildShopping(body.from, body.to));
  }),

  http.get(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/shopping\/lists\/[^/]+$/.test(url.pathname);
  }, ({ request }) => {
    const url = new URL(request.url);
    const listId = url.pathname.split('/').pop()!;
    const list = getDemoStore().shoppingLists.get(listId);
    if (!list) return HttpResponse.json({ code: 'NOT_FOUND', message: 'List not found' }, { status: 404 });
    return json(list);
  }),

  http.patch(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/shopping\/lists\/[^/]+\/lines\/[^/]+$/.test(url.pathname);
  }, async ({ request }) => {
    const url = new URL(request.url);
    const parts = url.pathname.split('/');
    const lineId = parts.pop()!;
    parts.pop();
    const listId = parts.pop()!;
    const body = (await request.json()) as { purchased?: boolean };
    const list = getDemoStore().shoppingLists.get(listId);
    if (!list) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Not found' }, { status: 404 });
    const line = list.lines.find((l) => l.lineId === lineId);
    if (!line) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Line not found' }, { status: 404 });
    if (body.purchased != null) line.purchased = body.purchased;
    return json(line);
  }),

  http.delete(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/shopping\/lists\/[^/]+\/lines\/[^/]+$/.test(url.pathname);
  }, ({ request }) => {
    const url = new URL(request.url);
    const parts = url.pathname.split('/');
    const lineId = parts.pop()!;
    parts.pop();
    const listId = parts.pop()!;
    const list = getDemoStore().shoppingLists.get(listId);
    if (list) {
      list.lines = list.lines.filter((l) => l.lineId !== lineId);
      list.empty = list.lines.length === 0;
    }
    return new HttpResponse(null, { status: 204 });
  }),

  http.post(({ request }) => {
    const url = new URL(request.url);
    return /\/bff\/v1\/shopping\/lists\/[^/]+\/lines$/.test(url.pathname);
  }, async ({ request }) => {
    const url = new URL(request.url);
    const parts = url.pathname.split('/');
    parts.pop();
    const listId = parts.pop()!;
    const body = (await request.json()) as { displayName: string; productCategory?: string };
    const list = getDemoStore().shoppingLists.get(listId);
    if (!list) return HttpResponse.json({ code: 'NOT_FOUND', message: 'Not found' }, { status: 404 });
    const line = {
      lineId: crypto.randomUUID(),
      displayName: body.displayName,
      quantity: null,
      unit: null,
      productCategory: body.productCategory ?? 'other',
      purchased: false,
      sourceRecipeIds: [] as string[],
    };
    list.lines.push(line);
    list.empty = false;
    return json(line, 201);
  }),

  http.post(({ request }) => {
    const url = new URL(request.url);
    return matchBff('/auth/login', url) || matchBff('/auth/register', url);
  }, () => {
    getDemoStore().loggedIn = true;
    return new HttpResponse(null, { status: 204 });
  }),

  http.post(({ request }) => matchBff('/auth/logout', new URL(request.url)), () => {
    getDemoStore().loggedIn = false;
    return new HttpResponse(null, { status: 204 });
  }),

  http.post(({ request }) => matchBff('/auth/password', new URL(request.url)), () =>
    new HttpResponse(null, { status: 204 }),
  ),
];
