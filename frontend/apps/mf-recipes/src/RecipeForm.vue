<script setup lang="ts">
import {
  PRODUCT_CATEGORY_OPTIONS,
  PRODUCT_CATEGORY_OTHER,
  bffErrorMessage,
  normalizeProductCategory,
  type Ingredient,
  type Recipe,
} from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { computed, h, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const isCreate = ref(route.name === 'recipe-new');

const shellTitle = computed(() => (isCreate.value ? 'Новый рецепт' : 'Редактор рецепта'));

function applyRecipeFormShell(): void {
  setShellHeader({
    ariaLabel: 'Шапка редактора рецепта',
    title: shellTitle.value,
    showAppNav: true,
    eyebrow: null,
    leadingRender: null,
    sublineRender: null,
    actionsRender: () =>
      h(RouterLink, { to: '/recipes', class: 'ui-app-header-link--accent' }, () => 'К библиотеке'),
  });
}

watch(shellTitle, applyRecipeFormShell);
const loading = ref(!isCreate.value);
const saving = ref(false);
const error = ref('');

const title = ref('');
const stepsText = ref('');
const cookTimeMinutes = ref<number | ''>('');
const mealCategory = ref('');
const sourceUrl = ref('');
const note = ref('');
const imageUrl = ref('');
const proteinG = ref<number | ''>('');
const fatG = ref<number | ''>('');
const carbsG = ref<number | ''>('');
const calories = ref<number | ''>('');

type IngredientRow = Ingredient & { toTaste: boolean };

const ingredients = reactive<IngredientRow[]>([
  { name: '', productCategory: PRODUCT_CATEGORY_OTHER, quantity: null, unit: '', toTaste: false },
]);

function addIngredient(): void {
  ingredients.push({ name: '', productCategory: PRODUCT_CATEGORY_OTHER, quantity: null, unit: '', toTaste: false });
}

function removeIngredient(i: number): void {
  if (ingredients.length > 1) {
    ingredients.splice(i, 1);
  }
}

function onToTasteChange(ing: IngredientRow): void {
  if (ing.toTaste) {
    ing.quantity = null;
    ing.unit = '';
  }
}

function parseSteps(): string[] {
  return stepsText.value
    .split('\n')
    .map((s) => s.trim())
    .filter(Boolean);
}

function payloadFromForm(): Record<string, unknown> {
  const ings = ingredients
    .filter((i) => i.name.trim() !== '')
    .map((i) => ({
      name: i.name.trim(),
      productCategory: normalizeProductCategory(i.productCategory),
      quantity:
        i.toTaste || i.quantity == null || i.quantity === '' ? null : Number(i.quantity),
      unit: i.toTaste ? undefined : i.unit?.trim() || undefined,
    }));
  const body: Record<string, unknown> = {
    title: title.value.trim(),
    ingredients: ings,
    steps: parseSteps(),
  };
  if (cookTimeMinutes.value !== '') {
    body.cookTimeMinutes = Number(cookTimeMinutes.value);
  }
  if (mealCategory.value.trim()) {
    body.mealCategory = mealCategory.value.trim();
  }
  if (sourceUrl.value.trim()) {
    body.sourceUrl = sourceUrl.value.trim();
  }
  if (note.value.trim()) {
    body.note = note.value.trim();
  }
  if (imageUrl.value.trim()) {
    body.imageUrl = imageUrl.value.trim();
  }
  const nutrition: Record<string, number> = {};
  if (proteinG.value !== '') nutrition.proteinG = Number(proteinG.value);
  if (fatG.value !== '') nutrition.fatG = Number(fatG.value);
  if (carbsG.value !== '') nutrition.carbsG = Number(carbsG.value);
  if (calories.value !== '') nutrition.calories = Number(calories.value);
  if (Object.keys(nutrition).length) {
    body.nutrition = nutrition;
  }
  return body;
}

async function loadEdit(): Promise<void> {
  const id = route.params.id as string;
  loading.value = true;
  error.value = '';
  try {
    const r = await bff.json<Recipe>(`/recipes/${id}`);
    title.value = r.title;
    stepsText.value = (r.steps ?? []).join('\n');
    cookTimeMinutes.value = r.cookTimeMinutes ?? '';
    mealCategory.value = r.mealCategory ?? '';
    sourceUrl.value = r.sourceUrl ?? '';
    note.value = r.note ?? '';
    imageUrl.value = r.imageUrl ?? '';
    proteinG.value = r.nutrition?.proteinG ?? '';
    fatG.value = r.nutrition?.fatG ?? '';
    carbsG.value = r.nutrition?.carbsG ?? '';
    calories.value = r.nutrition?.calories ?? '';
    ingredients.splice(
      0,
      ingredients.length,
      ...(r.ingredients.length
        ? r.ingredients.map((x) => ({
            ...x,
            productCategory: normalizeProductCategory(x.productCategory),
            toTaste: x.quantity == null && x.unit == null,
          }))
        : [{ name: '', productCategory: PRODUCT_CATEGORY_OTHER, quantity: null, unit: '', toTaste: false }]),
    );
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

function applyImportDraft(): void {
  if (route.query.fromImport !== '1') {
    return;
  }
  const raw = sessionStorage.getItem('meal_import_draft');
  if (!raw) {
    return;
  }
  try {
    const d = JSON.parse(raw) as {
      title?: string;
      steps?: string[];
      ingredients?: Array<{ name?: string; quantity?: number | null; unit?: string; productCategory?: string }>;
      sourceUrl?: string | null;
      cookTimeMinutes?: number | null;
      mealCategory?: string | null;
      imageUrl?: string | null;
      nutrition?: { proteinG?: number; fatG?: number; carbsG?: number; calories?: number } | null;
    };
    title.value = d.title ?? '';
    stepsText.value = (d.steps ?? []).join('\n');
    cookTimeMinutes.value = d.cookTimeMinutes ?? '';
    mealCategory.value = d.mealCategory ?? '';
    sourceUrl.value = d.sourceUrl ?? '';
    imageUrl.value = d.imageUrl ?? '';
    proteinG.value = d.nutrition?.proteinG ?? '';
    fatG.value = d.nutrition?.fatG ?? '';
    carbsG.value = d.nutrition?.carbsG ?? '';
    calories.value = d.nutrition?.calories ?? '';
    const ings = (d.ingredients ?? []).map((x) => ({
      name: x.name ?? '',
      productCategory: normalizeProductCategory(x.productCategory),
      quantity: x.quantity ?? null,
      unit: x.unit ?? '',
      toTaste: (x.quantity == null || x.quantity === undefined) && !x.unit,
    }));
    ingredients.splice(
      0,
      ingredients.length,
      ...(ings.length
        ? ings
        : [{ name: '', productCategory: PRODUCT_CATEGORY_OTHER, quantity: null, unit: '', toTaste: false }]),
    );
    sessionStorage.removeItem('meal_import_draft');
  } catch {
    sessionStorage.removeItem('meal_import_draft');
  }
}

onMounted(() => {
  isCreate.value = route.name === 'recipe-new';
  applyRecipeFormShell();
  if (!isCreate.value) {
    void loadEdit();
  } else {
    loading.value = false;
    applyImportDraft();
  }
});

watch(
  () => route.name,
  (n) => {
    isCreate.value = n === 'recipe-new';
    if (isCreate.value) {
      title.value = '';
      stepsText.value = '';
      cookTimeMinutes.value = '';
      mealCategory.value = '';
      sourceUrl.value = '';
      note.value = '';
      imageUrl.value = '';
      proteinG.value = '';
      fatG.value = '';
      carbsG.value = '';
      calories.value = '';
      ingredients.splice(0, ingredients.length, {
        name: '',
        productCategory: PRODUCT_CATEGORY_OTHER,
        quantity: null,
        unit: '',
        toTaste: false,
      });
      loading.value = false;
      applyImportDraft();
    } else {
      void loadEdit();
    }
    applyRecipeFormShell();
  },
);

watch(
  () => route.params.id,
  () => {
    if (route.name === 'recipe-edit') {
      void loadEdit();
    }
  },
);

function validateForm(): string | null {
  if (!title.value.trim()) {
    return 'Укажите название рецепта.';
  }
  const named = ingredients.filter((i) => i.name.trim() !== '');
  if (named.length === 0) {
    return 'Добавьте хотя бы один ингредиент с названием.';
  }
  for (const i of named) {
    if (!i.toTaste && i.quantity != null && i.quantity !== '' && Number(i.quantity) < 0) {
      return `Количество для «${i.name.trim()}» не может быть отрицательным.`;
    }
  }
  if (cookTimeMinutes.value !== '' && Number(cookTimeMinutes.value) < 1) {
    return 'Время приготовления должно быть не меньше 1 минуты.';
  }
  return null;
}

async function save(): Promise<void> {
  saving.value = true;
  error.value = '';
  const v = validateForm();
  if (v) {
    error.value = v;
    saving.value = false;
    return;
  }
  const body = payloadFromForm();
  try {
    if (isCreate.value) {
      const created = await bff.json<Recipe>('/recipes', {
        method: 'POST',
        body: JSON.stringify(body),
      });
      await router.push(`/recipes/${created.id}`);
    } else {
      const id = route.params.id as string;
      await bff.json<Recipe>(`/recipes/${id}`, {
        method: 'PATCH',
        body: JSON.stringify(body),
      });
      await router.push(`/recipes/${id}`);
    }
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <section class="mf-root">
    <p v-if="loading" class="muted">Загрузка…</p>
    <form v-else class="form" @submit.prevent="save">
      <label>
        Название *
        <input v-model="title" required />
      </label>
      <label>
        Шаги (каждый с новой строки)
        <textarea v-model="stepsText" rows="6"></textarea>
      </label>
      <label>
        Время (мин)
        <input v-model.number="cookTimeMinutes" type="number" min="1" />
      </label>
      <label>
        Категория приёма пищи
        <input v-model="mealCategory" />
      </label>
      <label>
        Источник (URL)
        <input v-model="sourceUrl" type="url" />
      </label>
      <label>
        URL изображения
        <input v-model="imageUrl" type="url" placeholder="https://..." />
      </label>
      <figure v-if="imageUrl.trim()" class="image-preview">
        <img :src="imageUrl.trim()" alt="Превью рецепта" />
      </figure>
      <label>
        Заметка
        <textarea v-model="note" rows="3" placeholder="Комментарий к рецепту"></textarea>
      </label>

      <fieldset>
        <legend>Пищевая ценность (на порцию)</legend>
        <div class="nutrition-row">
          <label>
            Белки (г)
            <input v-model.number="proteinG" type="number" min="0" step="any" />
          </label>
          <label>
            Жиры (г)
            <input v-model.number="fatG" type="number" min="0" step="any" />
          </label>
          <label>
            Углеводы (г)
            <input v-model.number="carbsG" type="number" min="0" step="any" />
          </label>
          <label>
            Ккал
            <input v-model.number="calories" type="number" min="0" step="any" />
          </label>
        </div>
      </fieldset>

      <fieldset>
        <legend>Ингредиенты *</legend>
        <div v-for="(ing, i) in ingredients" :key="i" class="ing-row">
          <input v-model="ing.name" placeholder="Название" />
          <label class="inline">
            <input v-model="ing.toTaste" type="checkbox" @change="onToTasteChange(ing)" />
            по вкусу
          </label>
          <input
            v-model.number="ing.quantity"
            type="number"
            step="any"
            placeholder="Кол-во"
            :disabled="ing.toTaste"
          />
          <input v-model="ing.unit" placeholder="Ед." :disabled="ing.toTaste" />
          <select v-model="ing.productCategory" aria-label="Категория продукта">
            <option v-for="o in PRODUCT_CATEGORY_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option>
            <option
              v-if="
                ing.productCategory &&
                !PRODUCT_CATEGORY_OPTIONS.some((o) => o.value === ing.productCategory)
              "
              :value="ing.productCategory"
            >
              {{ ing.productCategory }}
            </option>
          </select>
          <button type="button" class="btn small secondary remove-btn" @click="removeIngredient(i)">
            Удалить
          </button>
        </div>
        <button type="button" class="btn secondary small add-btn" @click="addIngredient">
          Добавить строку
        </button>
      </fieldset>

      <p v-if="error" class="err">{{ error }}</p>
      <button type="submit" class="btn" :disabled="saving">{{ saving ? 'Сохранение…' : 'Сохранить' }}</button>
    </form>
  </section>
</template>

<style scoped>
.mf-root {
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-md);
  color: var(--color-text-primary);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.form {
  display: grid;
  gap: var(--space-md);
  max-width: 52rem;
}

label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
}
input,
select,
textarea {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}

textarea {
  min-height: 132px;
}

.inline {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  white-space: nowrap;
}
.ing-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
  align-items: center;
  padding: var(--space-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
}

fieldset {
  margin: 0;
  padding: var(--space-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
}

legend {
  padding: 0 var(--space-xs);
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}

.btn {
  min-height: var(--button-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  font-size: var(--font-size-button);
  cursor: pointer;
}

.btn:hover {
  background: var(--color-accent-hover);
}

.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}

.btn.secondary:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.btn.small {
  min-height: 28px;
  padding: 0 var(--space-sm);
  font-size: var(--font-size-caption);
}

.remove-btn {
  justify-self: start;
}

.add-btn {
  margin-top: var(--space-xs);
}

.nutrition-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
}

.image-preview {
  margin: 0;
  max-width: 20rem;
}

.image-preview img {
  width: 100%;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  object-fit: cover;
}

.err {
  color: var(--color-error);
}

.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}

@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }

  .ing-row {
    grid-template-columns: 2fr auto 1fr 1fr 1.5fr auto;
    margin-bottom: var(--space-sm);
  }

  .remove-btn {
    justify-self: auto;
  }

  .nutrition-row {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
