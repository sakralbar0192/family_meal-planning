<script setup lang="ts">
import type { Recipe } from '@meal/bff-client';
import { bffErrorMessage } from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { UiButton, UiModalShell } from '@meal/ui-kit';
import { computed, h, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const recipe = ref<Recipe | null>(null);
const loading = ref(true);
const error = ref('');
const monthOpen = ref(false);
const pickDate = ref('');
const deleteBusy = ref(false);

const calYear = ref(new Date().getFullYear());
const calMonth = ref(new Date().getMonth());

function todayISODate(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

const calLabel = computed(() => {
  const d = new Date(calYear.value, calMonth.value, 1);
  return d.toLocaleString('ru', { month: 'long', year: 'numeric' });
});

const calCells = computed(() => {
  const first = new Date(calYear.value, calMonth.value, 1);
  const startPad = (first.getDay() + 6) % 7;
  const daysInMonth = new Date(calYear.value, calMonth.value + 1, 0).getDate();
  const cells: { d: number | null; iso: string | null; inMonth: boolean }[] = [];
  for (let i = 0; i < startPad; i++) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  for (let day = 1; day <= daysInMonth; day++) {
    const iso = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    cells.push({ d: day, iso, inMonth: true });
  }
  while (cells.length % 7 !== 0) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  while (cells.length < 42) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  return cells;
});

async function load(): Promise<void> {
  const id = route.params.id as string;
  if (!id) {
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    recipe.value = await bff.json<Recipe>(`/recipes/${id}`);
  } catch (e) {
    recipe.value = null;
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(
  () => route.params.id,
  () => load(),
);

function openMonthPicker(): void {
  pickDate.value = todayISODate();
  const [y, m] = pickDate.value.split('-').map(Number);
  calYear.value = y;
  calMonth.value = m - 1;
  monthOpen.value = true;
}

function closeMonthModal(): void {
  monthOpen.value = false;
}

function selectCalendarDay(iso: string | null): void {
  if (!iso) {
    return;
  }
  pickDate.value = iso;
}

function prevMonth(): void {
  if (calMonth.value === 0) {
    calMonth.value = 11;
    calYear.value -= 1;
  } else {
    calMonth.value -= 1;
  }
}

function nextMonth(): void {
  if (calMonth.value === 11) {
    calMonth.value = 0;
    calYear.value += 1;
  } else {
    calMonth.value += 1;
  }
}

function goPlannerWithDate(): void {
  if (!recipe.value || !pickDate.value) {
    return;
  }
  monthOpen.value = false;
  void router.push({
    path: '/planner',
    query: {
      anchorDate: pickDate.value,
      focusDate: pickDate.value,
      recipeSearch: recipe.value.title,
    },
  });
}

async function deleteRecipe(): Promise<void> {
  if (!recipe.value || deleteBusy.value) {
    return;
  }
  if (!window.confirm(`Удалить рецепт «${recipe.value.title}»?`)) {
    return;
  }
  deleteBusy.value = true;
  try {
    await bff.fetch(`/recipes/${recipe.value.id}`, { method: 'DELETE' });
    await router.push('/recipes');
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    deleteBusy.value = false;
  }
}

function applyRecipeDetailShell(): void {
  if (loading.value) {
    setShellHeader({
      ariaLabel: 'Шапка рецепта',
      title: 'Загрузка…',
      titleAlign: 'center',
      showAppNav: true,
      eyebrow: null,
      leadingRender: null,
      sublineRender: null,
      actionsRender: null,
    });
    return;
  }
  if (error.value || !recipe.value) {
    setShellHeader({
      ariaLabel: 'Шапка рецепта',
      title: 'Рецепт',
      titleAlign: 'center',
      showAppNav: true,
      eyebrow: null,
      sublineRender: null,
      leadingRender: () =>
        h(RouterLink, { to: '/recipes', class: 'ui-app-header-link--accent' }, () => 'К библиотеке'),
      actionsRender: null,
    });
    return;
  }
  setShellHeader({
    ariaLabel: 'Шапка рецепта',
    title: recipe.value.title,
    titleAlign: 'center',
    showAppNav: true,
    eyebrow: null,
    sublineRender: null,
    leadingRender: () =>
      h(RouterLink, { to: '/recipes', class: 'ui-app-header-link--accent' }, () => 'К библиотеке'),
    actionsRender: () =>
      h(
        'button',
        {
          type: 'button',
          class: 'ui-app-header-link--accent',
          'data-testid': 'recipe-add-to-plan',
          onClick: openMonthPicker,
        },
        () => 'В план',
      ),
  });
}

watch([recipe, loading, error], applyRecipeDetailShell, { immediate: true });

</script>

<template>
  <section class="mf-root">
    <p v-if="loading" class="muted">Загрузка…</p>
    <p v-else-if="error" class="err">{{ error }}</p>
    <template v-else-if="recipe">
      <div class="recipe-toolbar" role="toolbar" aria-label="Действия с рецептом">
        <RouterLink class="toolbar-edit" :to="`/recipes/${recipe.id}/edit`">Редактировать</RouterLink>
        <UiButton variant="secondary" :disabled="deleteBusy" data-testid="recipe-delete" @click="deleteRecipe">
          {{ deleteBusy ? '…' : 'Удалить' }}
        </UiButton>
      </div>

      <figure v-if="recipe.imageUrl" class="hero-image">
        <img :src="recipe.imageUrl" :alt="recipe.title" />
      </figure>

      <section v-if="recipe.note" class="block note-block">
        <h3>Заметка</h3>
        <p>{{ recipe.note }}</p>
      </section>

      <section class="meta-row">
        <p v-if="recipe.cookTimeMinutes != null" class="meta-chip">
          Время: {{ recipe.cookTimeMinutes }} мин
        </p>
        <p v-if="recipe.mealCategory" class="meta-chip">Приём пищи: {{ recipe.mealCategory }}</p>
      </section>

      <section v-if="recipe.nutrition" class="block">
        <h3>Пищевая ценность</h3>
        <ul class="nutr">
          <li v-if="recipe.nutrition.proteinG != null">Белки: {{ recipe.nutrition.proteinG }} г</li>
          <li v-if="recipe.nutrition.fatG != null">Жиры: {{ recipe.nutrition.fatG }} г</li>
          <li v-if="recipe.nutrition.carbsG != null">Углеводы: {{ recipe.nutrition.carbsG }} г</li>
          <li v-if="recipe.nutrition.calories != null">Ккал: {{ recipe.nutrition.calories }}</li>
        </ul>
      </section>
      <p v-else class="muted">Пищевая ценность не заполнена — укажите в редакторе.</p>

      <section class="block">
        <h3>Ингредиенты</h3>
        <ul>
          <li v-for="(ing, i) in recipe.ingredients" :key="i">
            {{ ing.name }}
            <template v-if="ing.quantity != null"> — {{ ing.quantity }} {{ ing.unit ?? '' }}</template>
            <template v-else> — по вкусу</template>
            <span class="muted"> ({{ ing.productCategory }})</span>
          </li>
        </ul>
      </section>

      <section v-if="recipe.steps?.length" class="block">
        <h3>Шаги</h3>
        <ol>
          <li v-for="(s, i) in recipe.steps" :key="i">{{ s }}</li>
        </ol>
      </section>
    </template>

    <UiModalShell :open="monthOpen" title="Выберите день" @close="closeMonthModal">
      <div class="cal-modal-body">
        <p class="muted">Далее откроется планировщик с поиском по названию рецепта.</p>
        <div class="cal-nav">
          <UiButton type="button" variant="secondary" @click="prevMonth">←</UiButton>
          <strong>{{ calLabel }}</strong>
          <UiButton type="button" variant="secondary" @click="nextMonth">→</UiButton>
        </div>
        <div class="dow">
          <span v-for="d in ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']" :key="d">{{ d }}</span>
        </div>
        <div class="grid">
          <button
            v-for="(c, i) in calCells"
            :key="i"
            type="button"
            class="cell"
            :class="{ selected: c.iso === pickDate }"
            :disabled="!c.inMonth"
            @click="selectCalendarDay(c.iso)"
          >
            {{ c.d ?? '' }}
          </button>
        </div>
      </div>
      <template #actions>
        <UiButton variant="secondary" @click="closeMonthModal">Закрыть</UiButton>
        <UiButton @click="goPlannerWithDate">Перейти в планировщик</UiButton>
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
.recipe-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}
.toolbar-edit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: var(--button-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-text-primary);
  font-weight: 600;
  font-size: var(--font-size-button);
  text-decoration: none;
}
.toolbar-edit:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}
.hero-image {
  margin: 0 0 var(--space-md);
  max-width: 28rem;
}
.hero-image img {
  width: 100%;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  object-fit: cover;
}
.note-block p {
  margin: 0;
  white-space: pre-wrap;
}
.meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
}
.meta-chip {
  margin: 0;
  padding: var(--space-xs) var(--space-sm);
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: var(--font-size-caption);
}
.block {
  margin-top: var(--space-lg);
  padding: var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}
.block h3 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-body);
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: var(--color-error);
}
.cal-modal-body {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.cal-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-sm);
}
.cal-nav strong {
  font-size: var(--font-size-body);
  font-weight: 600;
}
.dow {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
  text-align: center;
  font-size: var(--font-size-caption);
}
.grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}
.cell {
  min-height: max(48px, var(--touch-target));
  min-width: max(48px, var(--touch-target));
  aspect-ratio: 1;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg);
  cursor: pointer;
  font-size: var(--font-size-caption);
}
.cell:disabled {
  opacity: 0.25;
  cursor: default;
}
.cell.selected:not(:disabled) {
  background: color-mix(in srgb, var(--color-accent) 18%, var(--color-bg));
  border-color: var(--color-accent);
  font-weight: 600;
}
@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }
}
</style>
