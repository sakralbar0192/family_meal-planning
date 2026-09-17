<script setup lang="ts">
import {
  bffErrorFromResponse,
  bffErrorMessage,
  normalizeProductCategory,
  productCategoryLabel,
  type ShoppingLine,
  type ShoppingListDetail,
} from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { computed, h, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';
import { UiButton } from '@meal/ui-kit';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const detail = ref<ShoppingListDetail | null>(null);
const loading = ref(true);
const error = ref('');

const manualName = ref('');
const manualQty = ref<number | ''>('');
const manualUnit = ref('');
const manualCat = ref('');

const listId = computed(() => route.params.listId as string);

const showEmpty = computed(() => {
  if (loading.value || !detail.value) {
    return false;
  }
  const n = detail.value.lines?.length ?? 0;
  if (n > 0) {
    return false;
  }
  return detail.value.empty === true || route.query.empty === '1' || n === 0;
});

async function load(): Promise<void> {
  if (!listId.value) {
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    detail.value = await bff.json<ShoppingListDetail>(`/shopping/lists/${listId.value}`);
  } catch (e) {
    detail.value = null;
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
    applyShoppingShell();
  }
}

onMounted(() => {
  applyShoppingShell();
  void load();
});
watch(listId, () => load());

function formatShoppingPeriod(from: string, to: string): string {
  const d1 = new Date(`${from}T12:00:00`);
  const d2 = new Date(`${to}T12:00:00`);
  const y1 = d1.getFullYear();
  const y2 = d2.getFullYear();
  const m1 = d1.getMonth();
  const m2 = d2.getMonth();
  if (y1 === y2 && m1 === m2) {
    const monthYear = d1.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' });
    return `Период: ${d1.getDate()}—${d2.getDate()} ${monthYear}`;
  }
  if (y1 === y2) {
    const a = d1.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
    const b = d2.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
    return `Период: ${a} — ${b}`;
  }
  const a = d1.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
  const b = d2.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
  return `Период: ${a} — ${b}`;
}

const shoppingPeriodLabel = computed(() =>
  detail.value ? formatShoppingPeriod(detail.value.from, detail.value.to) : '',
);

const grouped = computed(() => {
  const lines = detail.value?.lines ?? [];
  const m = new Map<string, ShoppingLine[]>();
  for (const line of lines) {
    const key = normalizeProductCategory(line.productCategory);
    if (!m.has(key)) {
      m.set(key, []);
    }
    m.get(key)!.push(line);
  }
  const keys = [...m.keys()].sort((a, b) => {
    if (a === 'прочее') {
      return 1;
    }
    if (b === 'прочее') {
      return -1;
    }
    return productCategoryLabel(a).localeCompare(productCategoryLabel(b), 'ru');
  });
  return keys.map((k) => ({ category: productCategoryLabel(k), lines: m.get(k)! }));
});

async function togglePurchased(line: ShoppingLine): Promise<void> {
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines/${line.lineId}`, {
    method: 'PATCH',
    body: JSON.stringify({ purchased: !line.purchased }),
  });
  if (!res.ok) {
    error.value = (await bffErrorFromResponse(res)).message;
    return;
  }
  await load();
}

async function removeLine(line: ShoppingLine): Promise<void> {
  if (!confirm(`Удалить «${line.displayName}»?`)) {
    return;
  }
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines/${line.lineId}`, {
    method: 'DELETE',
  });
  if (!res.ok) {
    error.value = (await bffErrorFromResponse(res)).message;
    return;
  }
  await load();
}

async function addManual(): Promise<void> {
  if (!manualName.value.trim()) {
    error.value = 'Укажите название продукта.';
    return;
  }
  const body: Record<string, unknown> = { displayName: manualName.value.trim() };
  if (manualQty.value !== '') {
    body.quantity = Number(manualQty.value);
  }
  if (manualUnit.value.trim()) {
    body.unit = manualUnit.value.trim();
  }
  if (manualCat.value.trim()) {
    body.productCategory = manualCat.value.trim();
  }
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines`, {
    method: 'POST',
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    error.value = (await bffErrorFromResponse(res)).message;
    return;
  }
  manualName.value = '';
  manualQty.value = '';
  manualUnit.value = '';
  manualCat.value = '';
  await load();
}

async function exportText(): Promise<void> {
  const lines = detail.value?.lines ?? [];
  const parts: string[] = [];
  if (detail.value) {
    parts.push(`Период: ${detail.value.from} — ${detail.value.to}`);
  }
  for (const g of grouped.value) {
    parts.push(`\n[${g.category}]`);
    for (const l of g.lines) {
      const q = l.quantity != null ? `${l.quantity} ${l.unit ?? ''}`.trim() : '';
      parts.push(`${l.purchased ? '✓' : '○'} ${l.displayName}${q ? ` — ${q}` : ''}`);
    }
  }
  const text = parts.join('\n');
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    error.value = 'Не удалось скопировать в буфер';
  }
}

function goPlanner(): void {
  void router.push({ path: '/planner' });
}

function applyShoppingShell(): void {
  setShellHeader({
    ariaLabel: 'Шапка списка покупок',
    title: 'Список покупок',
    showAppNav: true,
    eyebrow: null,
    leadingRender: null,
    actionsRender: () =>
      h(RouterLink, { to: '/planner', class: 'ui-app-header-link--accent' }, () => 'К планировщику'),
    sublineRender: detail.value
      ? () =>
          h('div', { class: 'shopping-period-line', 'data-testid': 'shopping-period' }, [
            h('span', null, shoppingPeriodLabel.value),
            h(RouterLink, { to: '/planner', class: 'ui-app-header-link--accent' }, () => 'Изменить период'),
          ])
      : null,
  });
}

watch([detail, shoppingPeriodLabel], applyShoppingShell, { deep: true });
</script>

<template>
  <section class="mf-root">
    <p v-if="loading" class="muted">Загрузка…</p>
    <p v-else-if="error" class="err">{{ error }}</p>

    <template v-else-if="detail">
      <div v-if="showEmpty" class="empty soft" data-testid="shopping-empty-state">
        <p>За период не было назначений — можно добавить продукты вручную или вернуться в планировщик.</p>
        <UiButton type="button" variant="secondary" @click="goPlanner">Планировщик</UiButton>
      </div>
      <div class="toolbar">
        <UiButton type="button" variant="secondary" data-testid="shopping-copy-list" @click="exportText">
          Копировать список
        </UiButton>
      </div>

      <div v-for="g in grouped" :key="g.category" class="group" data-testid="shopping-group">
        <h3>{{ g.category }}</h3>
        <ul class="lines">
          <li v-for="line in g.lines" :key="line.lineId" class="line" data-testid="shopping-line">
            <label class="check">
              <input
                type="checkbox"
                data-testid="shopping-line-purchased"
                :checked="line.purchased"
                @change="togglePurchased(line)"
              />
              <span :class="{ done: line.purchased }">{{ line.displayName }}</span>
            </label>
            <span v-if="line.quantity != null" class="qty">
              {{ line.quantity }} {{ line.unit ?? '' }}
            </span>
            <span v-if="line.mergeNote" class="muted">{{ line.mergeNote }}</span>
            <UiButton type="button" variant="danger" size="sm" data-testid="shopping-line-delete" @click="removeLine(line)">
              Удалить
            </UiButton>
          </li>
        </ul>
      </div>

      <section class="manual">
        <h3>Свой продукт</h3>
        <div class="manual-row">
          <input v-model="manualName" placeholder="Название" data-testid="shopping-manual-name" />
          <input v-model.number="manualQty" type="number" step="any" placeholder="Кол-во" />
          <input v-model="manualUnit" placeholder="Ед." />
          <input v-model="manualCat" placeholder="Категория" />
          <UiButton type="button" data-testid="shopping-manual-add" @click="addManual">Добавить</UiButton>
        </div>
      </section>
    </template>
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
.toolbar {
  margin: var(--space-md) 0;
  display: grid;
  grid-template-columns: 1fr;
}
.group {
  margin-bottom: var(--space-lg);
  padding: var(--space-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
}
.group h3 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-body);
}
.lines {
  list-style: none;
  margin: 0;
  padding: 0;
}
.line {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  padding: var(--space-sm);
  border-bottom: 1px solid var(--color-border);
  font-size: var(--font-size-caption);
}
.check {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  min-width: 0;
}
.done {
  text-decoration: line-through;
  color: var(--color-text-muted);
}
.qty {
  color: var(--color-text-secondary);
}
.manual {
  margin-top: var(--space-xl);
  padding: var(--space-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
}
.manual-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  margin-top: var(--space-sm);
}
.manual-row input {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
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
  padding: 0 var(--space-sm);
  font-size: var(--font-size-caption);
  justify-self: start;
}
.empty {
  padding: var(--space-lg);
  text-align: center;
}
.empty.soft {
  padding: var(--space-md);
  margin-bottom: var(--space-lg);
  text-align: left;
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: var(--color-error);
}
@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }
  .line {
    grid-template-columns: minmax(0, 1fr) auto auto auto;
    align-items: center;
  }
  .toolbar {
    grid-template-columns: max-content;
    justify-content: start;
  }
  .manual-row {
    grid-template-columns: 2fr 1fr 1fr 1.5fr auto;
    align-items: end;
  }
  .btn.small {
    justify-self: auto;
  }
}
@media (min-width: 1200px) {
  .head {
    grid-template-columns: max-content 1fr auto;
    align-items: center;
    column-gap: var(--space-md);
  }
}
</style>
