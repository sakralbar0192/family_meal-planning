<script setup lang="ts">
import {
  bffErrorFromResponse,
  bffErrorMessage,
  bffPath,
  type DayPlan,
  type WeekPlanResponse,
} from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { h, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { MEAL_SLOT_CODES, MEAL_SLOT_LABELS } from './mealSlots';
import { useBff } from './useBff';
import type { RecipeListResponse, RecipeSummary } from '@meal/bff-client';
import { UiButton, UiModalShell, UiRecipeCard } from '@meal/ui-kit';

const router = useRouter();
const bff = useBff();

const items = ref<RecipeSummary[]>([]);
const total = ref(0);
const loading = ref(true);
const error = ref('');

const q = ref('');
const mealCategory = ref('');
const maxCookTimeMinutes = ref<number | ''>('');

const planModalOpen = ref(false);
const planStep = ref<1 | 2>(1);
const planRecipe = ref<RecipeSummary | null>(null);
const planDate = ref('');
const planSlotCode = ref<string>(MEAL_SLOT_CODES[0]);
const planBusy = ref(false);
const planError = ref('');

const toastOpen = ref(false);
const toastContext = ref<{ recipeTitle: string; date: string; slotLabel: string } | null>(null);

async function loadList(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const path = bffPath('/recipes', {
      q: q.value || undefined,
      mealCategory: mealCategory.value || undefined,
      maxCookTimeMinutes:
        maxCookTimeMinutes.value === '' ? undefined : maxCookTimeMinutes.value,
      limit: 50,
      offset: 0,
    });
    const res = await bff.json<RecipeListResponse>(path);
    items.value = res.items;
    total.value = res.total;
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  setShellHeader({
    ariaLabel: 'Шапка библиотеки',
    title: 'Рецепты',
    showAppNav: true,
    eyebrow: null,
    leadingRender: null,
    sublineRender: null,
    actionsRender: () =>
      h(RouterLink, { to: '/recipes/import', class: 'ui-app-header-link--accent' }, () => 'Импорт по URL'),
  });
  void loadList();
});

function openPlanModal(r: RecipeSummary): void {
  planRecipe.value = r;
  planDate.value = todayISODate();
  planSlotCode.value = MEAL_SLOT_CODES[0];
  planError.value = '';
  planStep.value = 1;
  planModalOpen.value = true;
}

function closePlanModal(): void {
  planModalOpen.value = false;
  planRecipe.value = null;
  planStep.value = 1;
  planError.value = '';
}

function goToPlanStep2(): void {
  if (!planDate.value) {
    planError.value = 'Укажите дату.';
    return;
  }
  planError.value = '';
  planStep.value = 2;
}

function backToPlanStep1(): void {
  planStep.value = 1;
  planError.value = '';
}

function todayISODate(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function findSlot(week: WeekPlanResponse, date: string, code: string) {
  const day = week.days.find((d: DayPlan) => d.date === date);
  if (!day) {
    return null;
  }
  return day.slots.find((s) => s.slotCode === code) ?? null;
}

async function confirmAddToPlan(): Promise<void> {
  if (!planRecipe.value || !planDate.value) {
    return;
  }
  planBusy.value = true;
  planError.value = '';
  const recipeTitle = planRecipe.value.title;
  const dateIso = planDate.value;
  const slotLabel = MEAL_SLOT_LABELS[planSlotCode.value];
  try {
    const week = await bff.json<WeekPlanResponse>(
      bffPath('/plan/week', { anchorDate: planDate.value }),
    );
    const slot = findSlot(week, planDate.value, planSlotCode.value);
    if (!slot) {
      planError.value = 'Слот не найден для выбранной даты.';
      return;
    }
    const nextIds = [...new Set([...slot.recipeIds, planRecipe.value.id])];
    const res = await bff.fetch(`/plan/slots/${slot.slotId}`, {
      method: 'PATCH',
      body: JSON.stringify({
        recipeIds: nextIds,
        expectedVersion: slot.version,
      }),
    });
    if (!res.ok) {
      const err = await bffErrorFromResponse(res);
      if (err.code === 'VERSION_CONFLICT') {
        planError.value =
          'План изменился. Закройте окно и откройте «В план» снова, либо обновите планировщик.';
        return;
      }
      planError.value = err.message;
      return;
    }
    closePlanModal();
    toastContext.value = { recipeTitle, date: dateIso, slotLabel };
    toastOpen.value = true;
  } catch (e) {
    planError.value = bffErrorMessage(e);
  } finally {
    planBusy.value = false;
  }
}

function closeToast(): void {
  toastOpen.value = false;
  toastContext.value = null;
}

function openPlannerFromToast(): void {
  if (!toastContext.value) {
    return;
  }
  const { date } = toastContext.value;
  closeToast();
  void router.push({
    path: '/planner',
    query: { anchorDate: date, focusDate: date },
  });
}

async function deleteRecipe(r: RecipeSummary): Promise<void> {
  if (!confirm(`Удалить «${r.title}»?`)) {
    return;
  }
  try {
    const res = await bff.fetch(`/recipes/${r.id}`, { method: 'DELETE' });
    if (!res.ok) {
      error.value = (await bffErrorFromResponse(res)).message;
      return;
    }
    await loadList();
  } catch (e) {
    error.value = bffErrorMessage(e);
  }
}

</script>

<template>
  <section class="mf-root">
    <div class="library-quick">
      <RouterLink class="quick-link" to="/recipes/new">Создать рецепт</RouterLink>
      <RouterLink class="quick-link" to="/planner?pickDay=1">Планировщик</RouterLink>
    </div>

    <form class="filters" @submit.prevent="loadList">
      <input v-model="q" type="search" placeholder="Поиск по названию" aria-label="Поиск" />
      <input v-model="mealCategory" type="text" placeholder="Категория приёма пищи" />
      <input
        v-model.number="maxCookTimeMinutes"
        type="number"
        min="1"
        placeholder="Макс. время (мин)"
      />
      <button type="submit" class="btn">Найти</button>
    </form>

    <div class="list-state">
      <p v-if="loading" class="muted">Загрузка…</p>
      <p v-else-if="error" class="err">{{ error }}</p>
      <p v-else class="muted">Всего: {{ total }}</p>
    </div>

    <ul v-if="!loading" class="cards">
      <li v-for="r in items" :key="r.id" class="card-item">
        <UiRecipeCard>
          <template #top>
            <RouterLink class="card-link" :to="`/recipes/${r.id}`">
              <h4 class="card-title">{{ r.title }}</h4>
              <p v-if="r.cookTimeMinutes != null" class="card-meta">{{ r.cookTimeMinutes }} мин</p>
              <span v-if="r.mealCategory" class="card-badge">{{ r.mealCategory }}</span>
            </RouterLink>
          </template>
          <template #actions>
            <UiButton size="sm" @click="openPlanModal(r)">В план</UiButton>
            <RouterLink class="btn small secondary" :to="`/recipes/${r.id}/edit`">Изменить</RouterLink>
            <UiButton size="sm" variant="danger" @click="deleteRecipe(r)">Удалить</UiButton>
          </template>
        </UiRecipeCard>
      </li>
    </ul>

    <UiModalShell
      v-if="planModalOpen"
      :open="planModalOpen"
      :title="
        planStep === 1 ? 'Добавить в план (шаг 1 из 2)' : 'Добавить в план (шаг 2 из 2)'
      "
      @close="closePlanModal"
    >
      <div class="plan-modal-body">
        <p v-if="planRecipe" class="recipe-line">{{ planRecipe.title }}</p>

        <template v-if="planStep === 1">
          <div class="plan-modal-panel">
            <p class="muted plan-hint">Выберите день для блюда в плане.</p>
            <label>
              Дата
              <input v-model="planDate" type="date" />
            </label>
          </div>
        </template>

        <template v-else>
          <div class="plan-modal-panel">
            <p class="muted plan-hint">Выберите один из шести фиксированных приёмов пищи.</p>
            <label>
              Приём пищи
              <select v-model="planSlotCode">
                <option v-for="c in MEAL_SLOT_CODES" :key="c" :value="c">
                  {{ MEAL_SLOT_LABELS[c] }}
                </option>
              </select>
            </label>
          </div>
        </template>

        <p v-if="planError" class="err">{{ planError }}</p>
      </div>
      <template #actions>
        <template v-if="planStep === 1">
          <UiButton variant="secondary" :disabled="planBusy" @click="closePlanModal">Отмена</UiButton>
          <UiButton :disabled="planBusy" @click="goToPlanStep2">Далее</UiButton>
        </template>
        <template v-else>
          <UiButton variant="secondary" :disabled="planBusy" @click="backToPlanStep1">Назад</UiButton>
          <UiButton :disabled="planBusy" @click="confirmAddToPlan">Добавить</UiButton>
        </template>
      </template>
    </UiModalShell>

    <UiModalShell
      v-if="toastOpen"
      :open="toastOpen"
      title="Рецепт добавлен"
      @close="closeToast"
    >
      <div class="toast-body">
        <p v-if="toastContext" class="muted">
          {{ toastContext.recipeTitle }} · {{ toastContext.date }} · {{ toastContext.slotLabel }}
        </p>
        <p class="muted toast-sub">Блюдо добавлено в план.</p>
      </div>
      <template #actions>
        <UiButton variant="secondary" data-testid="plan-toast-planner" @click="openPlannerFromToast">
          В планировщик
        </UiButton>
        <UiButton data-testid="plan-toast-ok" @click="closeToast">OK</UiButton>
      </template>
    </UiModalShell>
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

.library-quick {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.quick-link {
  font-size: var(--font-size-caption);
  font-weight: 600;
  color: var(--color-text-primary);
  text-decoration: none;
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.quick-link:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.filters {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.filters input {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: var(--button-min-height);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  font-size: var(--font-size-button);
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

.btn.danger {
  background: var(--color-error);
  color: var(--color-text-on-accent);
}

.btn.danger:hover {
  background: color-mix(in srgb, var(--color-error) 85%, black);
}

.btn.small {
  min-height: 28px;
  padding: var(--space-xs) var(--space-sm);
  font-size: var(--font-size-caption);
}

.list-state {
  margin-bottom: var(--space-sm);
}

.cards {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}

.card-item {
  min-width: 0;
}

.card-link {
  display: grid;
  gap: var(--space-sm);
  text-decoration: none;
  color: inherit;
}

.card-title {
  margin: 0;
  font-size: var(--font-size-body);
}

.card-meta {
  margin: 0;
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}

.card-badge {
  display: inline-flex;
  width: fit-content;
  border-radius: 999px;
  padding: 2px 8px;
  font-size: var(--font-size-caption);
  background: color-mix(in srgb, var(--color-surface) 85%, var(--color-accent));
}

.muted {
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}

.err {
  color: var(--color-error);
  font-size: var(--font-size-body);
}

.plan-modal-body {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.recipe-line {
  margin: 0;
  font-size: var(--font-size-body);
  font-weight: 600;
  color: var(--color-text-primary);
}

.plan-modal-panel {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
}

.plan-hint {
  margin: 0;
}

.plan-modal-body label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
}

.plan-modal-body input,
.plan-modal-body select {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: inherit;
}

.toast-body {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.toast-body p {
  margin: 0;
}

.toast-sub {
  font-size: var(--font-size-caption);
}

@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }

  .filters {
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) max-content;
    align-items: center;
  }

  .cards {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

}

@media (min-width: 1200px) {
  .cards {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
