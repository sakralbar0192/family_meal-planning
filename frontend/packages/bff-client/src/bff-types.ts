/** Выравнивание с contracts/openapi/bff.openapi.yaml (фрагмент). */

export type MealSlotCode =
  | 'BREAKFAST'
  | 'SECOND_BREAKFAST'
  | 'LUNCH'
  | 'SNACK'
  | 'DINNER'
  | 'LATE_DINNER';

export type RecipeSummary = {
  id: string;
  title: string;
  cookTimeMinutes?: number | null;
  mealCategory?: string | null;
};

export type RecipeListResponse = {
  items: RecipeSummary[];
  total: number;
};

export type Ingredient = {
  name: string;
  quantity?: number | null;
  unit?: string;
  productCategory: string;
};

export type Nutrition = {
  proteinG?: number;
  fatG?: number;
  carbsG?: number;
  calories?: number;
};

export type Recipe = {
  id: string;
  title: string;
  steps?: string[];
  cookTimeMinutes?: number | null;
  mealCategory?: string | null;
  nutrition?: Nutrition | null;
  ingredients: Ingredient[];
  sourceUrl?: string | null;
  createdAt: string;
  updatedAt: string;
};

export type RecipeDraft = {
  title: string;
  steps?: string[];
  cookTimeMinutes?: number | null;
  mealCategory?: string | null;
  nutrition?: Nutrition | null;
  ingredients?: Array<Record<string, unknown>>;
  sourceUrl?: string | null;
  imageUrl?: string | null;
};

export type SlotAssignment = {
  slotId: string;
  date: string;
  slotCode: MealSlotCode;
  recipeIds: string[];
  version: number;
};

export type DayPlan = {
  date: string;
  slots: SlotAssignment[];
};

export type WeekPlanResponse = {
  weekStart: string;
  weekEnd: string;
  days: DayPlan[];
  recipeSearchHint?: string;
};

export type BuildListResponse = {
  listId: string;
  from: string;
  to: string;
  replaced: boolean;
  empty: boolean;
};

export type ShoppingLine = {
  lineId: string;
  displayName: string;
  quantity?: number | null;
  unit?: string | null;
  productCategory?: string | null;
  purchased: boolean;
  mergeNote?: string;
  sourceRecipeIds?: string[];
};

export type ShoppingListDetail = {
  listId: string;
  from: string;
  to: string;
  /** true, если нет строк снимка (см. OpenAPI shopping-list / bff). */
  empty: boolean;
  lines: ShoppingLine[];
};

export type BffErrorBody = {
  code: string;
  message: string;
  details?: Record<string, unknown>;
};
