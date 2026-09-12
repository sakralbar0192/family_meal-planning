import type {
  BuildListResponse,
  Recipe,
  RecipeListResponse,
  ShoppingListDetail,
  WeekPlanResponse,
} from '@meal/bff-client';

const DEMO_USER = 'demo-user';

function isoDate(d: Date): string {
  return d.toISOString().slice(0, 10);
}

function mondayOf(date: Date): Date {
  const d = new Date(date);
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  d.setDate(d.getDate() + diff);
  return d;
}

const today = new Date();
const weekStartDate = mondayOf(today);
const weekDates = Array.from({ length: 7 }, (_, i) => {
  const d = new Date(weekStartDate);
  d.setDate(d.getDate() + i);
  return isoDate(d);
});

export const seedRecipes: Recipe[] = [
  {
    id: '11111111-1111-4111-8111-111111111101',
    title: 'Борщ классический',
    steps: ['Сварить бульон', 'Добавить овощи', 'Подавать со сметаной'],
    cookTimeMinutes: 90,
    mealCategory: 'Суп',
    nutrition: { proteinG: 12, fatG: 8, carbsG: 15, calories: 180 },
    ingredients: [
      { name: 'Говядина', quantity: 500, unit: 'г', productCategory: 'meat' },
      { name: 'Свёкла', quantity: 300, unit: 'г', productCategory: 'vegetables' },
      { name: 'Капуста', quantity: 200, unit: 'г', productCategory: 'vegetables' },
    ],
    sourceUrl: 'https://eda.ru/recepty/sup/borsch',
    note: 'Лучше на второй день.',
    imageUrl: null,
    createdAt: '2026-01-01T10:00:00Z',
    updatedAt: '2026-01-01T10:00:00Z',
  },
  {
    id: '11111111-1111-4111-8111-111111111102',
    title: 'Куриный суп',
    steps: ['Отварить курицу', 'Добавить овощи'],
    cookTimeMinutes: 45,
    mealCategory: 'Суп',
    nutrition: { proteinG: 18, fatG: 6, carbsG: 10, calories: 160 },
    ingredients: [
      { name: 'Куриное филе', quantity: 400, unit: 'г', productCategory: 'meat' },
      { name: 'Картофель', quantity: 2, unit: 'шт', productCategory: 'vegetables' },
      { name: 'Морковь', quantity: 1, unit: 'шт', productCategory: 'vegetables' },
    ],
    sourceUrl: 'https://povarenok.ru/recept/soup',
    note: null,
    imageUrl: null,
    createdAt: '2026-01-02T10:00:00Z',
    updatedAt: '2026-01-02T10:00:00Z',
  },
  {
    id: '11111111-1111-4111-8111-111111111103',
    title: 'Овсянка с ягодами',
    steps: ['Сварить овсянку', 'Добавить ягоды'],
    cookTimeMinutes: 15,
    mealCategory: 'Завтрак',
    nutrition: { calories: 220 },
    ingredients: [
      { name: 'Овсяные хлопья', quantity: 80, unit: 'г', productCategory: 'groceries' },
      { name: 'Молоко', quantity: 200, unit: 'мл', productCategory: 'dairy' },
      { name: 'Ягоды', quantity: null, unit: undefined, productCategory: 'vegetables' },
    ],
    sourceUrl: null,
    note: null,
    imageUrl: null,
    createdAt: '2026-01-03T10:00:00Z',
    updatedAt: '2026-01-03T10:00:00Z',
  },
  {
    id: '11111111-1111-4111-8111-111111111104',
    title: 'Паста карбонара',
    steps: ['Сварить пасту', 'Смешать с соусом'],
    cookTimeMinutes: 25,
    mealCategory: 'Ужин',
    ingredients: [
      { name: 'Спагетти', quantity: 200, unit: 'г', productCategory: 'groceries' },
      { name: 'Бекон', quantity: 100, unit: 'г', productCategory: 'meat' },
      { name: 'Parmesan', quantity: 50, unit: 'г', productCategory: 'dairy' },
    ],
    sourceUrl: null,
    note: null,
    imageUrl: null,
    createdAt: '2026-01-04T10:00:00Z',
    updatedAt: '2026-01-04T10:00:00Z',
  },
  {
    id: '11111111-1111-4111-8111-111111111105',
    title: 'Греческий салат',
    steps: ['Нарезать овощи', 'Заправить маслом'],
    cookTimeMinutes: 10,
    mealCategory: 'Салат',
    ingredients: [
      { name: 'Огурец', quantity: 1, unit: 'шт', productCategory: 'vegetables' },
      { name: 'Помидор', quantity: 2, unit: 'шт', productCategory: 'vegetables' },
      { name: 'Сыр фета', quantity: 150, unit: 'г', productCategory: 'dairy' },
    ],
    sourceUrl: null,
    note: null,
    imageUrl: null,
    createdAt: '2026-01-05T10:00:00Z',
    updatedAt: '2026-01-05T10:00:00Z',
  },
];

const slotCodes = [
  'BREAKFAST',
  'SECOND_BREAKFAST',
  'LUNCH',
  'SNACK',
  'DINNER',
  'LATE_DINNER',
] as const;

type SlotState = {
  slotId: string;
  date: string;
  slotCode: (typeof slotCodes)[number];
  recipeIds: string[];
  version: number;
};

function buildInitialSlots(): SlotState[] {
  const slots: SlotState[] = [];
  let n = 0;
  for (const date of weekDates) {
    for (const code of slotCodes) {
      n += 1;
      slots.push({
        slotId: `22222222-2222-4222-8222-${String(n).padStart(12, '0')}`,
        date,
        slotCode: code,
        recipeIds: [],
        version: 1,
      });
    }
  }
  const mondayDinner = slots.find((s) => s.date === weekDates[0] && s.slotCode === 'DINNER');
  if (mondayDinner) {
    mondayDinner.recipeIds = [seedRecipes[3].id];
  }
  return slots;
}

export type DemoStore = {
  recipes: Recipe[];
  slots: SlotState[];
  shoppingLists: Map<string, ShoppingListDetail>;
  loggedIn: boolean;
};

export function createDemoStore(): DemoStore {
  return {
    recipes: structuredClone(seedRecipes),
    slots: buildInitialSlots(),
    shoppingLists: new Map(),
    loggedIn: true,
  };
}

let store = createDemoStore();

export function resetDemoStore(): void {
  store = createDemoStore();
}

export function getDemoStore(): DemoStore {
  return store;
}

export function listRecipes(q?: string): RecipeListResponse {
  let items = store.recipes.map((r) => ({
    id: r.id,
    title: r.title,
    cookTimeMinutes: r.cookTimeMinutes,
    mealCategory: r.mealCategory,
  }));
  if (q) {
    const lower = q.toLowerCase();
    items = items.filter((r) => r.title.toLowerCase().includes(lower));
  }
  return { items, total: items.length };
}

export function weekPlan(anchorDate?: string, recipeSearch?: string): WeekPlanResponse {
  const anchor = anchorDate ?? isoDate(today);
  const start = mondayOf(new Date(`${anchor}T12:00:00`));
  const dates = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(start);
    d.setDate(d.getDate() + i);
    return isoDate(d);
  });
  return {
    weekStart: dates[0],
    weekEnd: dates[6],
    days: dates.map((date) => ({
      date,
      slots: store.slots
        .filter((s) => s.date === date)
        .map((s) => ({
          slotId: s.slotId,
          date: s.date,
          slotCode: s.slotCode,
          recipeIds: [...s.recipeIds],
          version: s.version,
        })),
    })),
    recipeSearchHint: recipeSearch,
  };
}

export function buildShopping(from: string, to: string): BuildListResponse {
  const listId = '33333333-3333-4333-8333-333333333301';
  const lines: ShoppingListDetail['lines'] = [];
  const agg = new Map<string, { quantity: number; unit: string; category: string; ids: string[] }>();

  for (const slot of store.slots) {
    if (slot.date < from || slot.date > to) continue;
    for (const rid of slot.recipeIds) {
      const recipe = store.recipes.find((r) => r.id === rid);
      if (!recipe) continue;
      for (const ing of recipe.ingredients) {
        const key = `${ing.name}|${ing.unit ?? ''}`;
        const cur = agg.get(key) ?? {
          quantity: 0,
          unit: ing.unit ?? '',
          category: ing.productCategory,
          ids: [],
        };
        if (ing.quantity != null) cur.quantity += ing.quantity;
        if (!cur.ids.includes(rid)) cur.ids.push(rid);
        agg.set(key, cur);
      }
    }
  }

  let lineNum = 0;
  for (const [key, val] of agg) {
    const name = key.split('|')[0];
    lineNum += 1;
    lines.push({
      lineId: `44444444-4444-4444-8444-${String(lineNum).padStart(12, '0')}`,
      displayName: name,
      quantity: val.quantity || null,
      unit: val.unit || null,
      productCategory: val.category,
      purchased: false,
      sourceRecipeIds: val.ids,
    });
  }

  const detail: ShoppingListDetail = {
    listId,
    from,
    to,
    empty: lines.length === 0,
    lines,
  };
  store.shoppingLists.set(listId, detail);
  return { listId, from, to, replaced: true, empty: detail.empty };
}

export { DEMO_USER, weekDates };
